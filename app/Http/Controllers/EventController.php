<?php

namespace App\Http\Controllers;

use App\Event;
use Illuminate\Http\Request;
use Validator;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Judge;
use App\User;
use App\Candidate;
use App\Category;
use App\Criteria;
use Crypt;
use App\Traits\Encryptable;
use App\Segments;
use App\EventSegment;
use App\SegmentCategory;


class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        // $events = Event::where('user_id',Auth::user()->id)->get();
        $events = Event::where('user_id',Auth::user()->id)->orderBy('date')->paginate(5);
        return view('events.index',['events'=>$events]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        return view('events.create');
    }

    public function storejudge(Request $request,$event_id)
    {
        $judgenumber = '';

        $event = Event::find($event_id); 

        $judge = Judge::where('user_id',$request->input('user_id'))
                        ->where('event_id',$event->id)
                        ->first();

        $user = User::find($request->input('user_id'));
        $judgecount = $event->users()->where('event_id',$event->id)->get()->count();
        $judgenumbers = Judge::where('event_id',$event->id)->pluck('judge_number')->toArray();

       
        // Judge Number
        $judgenumber = $judgecount+1;

        for($i = 1; $i <= $judgecount; $i++){
            if(!in_array($i,$judgenumbers)){
                $judgenumber = $i;
                break;
            }
        }

        if($judge){
            if($request->ajax()){
                return response(['warnings','Already added as judge to event']);
            }
            return redirect("/events/{$event->id}")
            ->with('warnings','Already added as judge to event');        
        }

        $event->users()->attach($user,['judge_number'=>$judgenumber]); 
            //    $newjudge =  Judge::create([
            //         'event_id'=>$event->id,
            //         'user_id'=>$user->id,
            //         'judge_number'=>$request->input('judge_number')
            //     ]);

        if($request->ajax()){
            return response(['success'=>'Added as Judge to event','judge'=>$event->users()->where('users.id',$user->id)->first(),'event'=>$event]);
        }
        return redirect("/events/{$event->id}")
                    ->with('success','Added as Judge to event');  
    }

    public function addjudge($event_id)
    {
        $event = Event::find($event_id);

        if($event){
            $users = User::all();
            return view('events.judge.create',['users'=>$users,'event'=>$event]);
        }

       return redirect('/events')
                    ->with('warning','Event not found');
    }

    public function editjudge(Request $request,$user_id,$event_id){

        $event = Event::find($event_id);
        $judge = $event->users()->where('user_id',$user_id)->first();

        if($event){
            if($judge){
                if($request->ajax()){
                    return response(['judge'=>$judge,'event'=>$event]);
                }
                return view('events.judge.edit',['judge'=>$judge,'event'=>$event]);
            }

            return redirect("/events/{$event->id}")
                            ->with('warnings','User not found');
        }
        return redirect()->route('events.index')
                            ->with('warnings','Event not found');
    }

    public function updatejudge(Request $request, $user_id, $event_id)
    {
        $event = Event::find($event_id);
        $judge = User::find($user_id);
        $judgenumber = Judge::where('event_id',$event->id)
                            ->where('judge_number',$request->input('judge_number'))
                            ->first();

        if($judgenumber){
            if($request->ajax()){
                return response(['warnings'=>'Judge number already used']);
            }
            return redirect("/events/{$event->id}")
                ->with('warnings','Judge number already used');
        }

        if($event){
            $event->users()->updateExistingPivot($judge->id,[
                'judge_number'=>$request->input('judge_number')
            ]);

            if($request->ajax()){
                return response(['success'=>'Judge Updated','user'=>$event->users()->where('users.id',$judge->id)->first(),'event'=>$event]);
            }
            return redirect("/events/{$event->id}")
                            ->with('success','Judge Updated');
        }
        if($request->ajax()){
            return response(['warnings'=>'Event not found']);
        }
        return redirect()->route('events.index')
                        ->with('success','Event not found');
    }

    public function removejudge(Request $request,$user_id, $event_id)
    {
        $event = Event::find($event_id);
        $user = User::find($user_id);

        if($event){
            if($user){
                $event->users()->detach($user->id); 
                if($request->ajax()){
                    return response(['success'=>'Judge Removed','judge'=>$user,'event'=>$event]);
                }
                return redirect("/events/{$event->id}")
                    ->with('success','Judge Removed');
            }
             if($request->ajax()){
                    return response(['warnings'=>'Judge not found']);
            }
            return redirect()->route('events.index')
                ->with('warnings','User not found');
        }
        if($request->ajax()){
            return response(['warnings'=>'Event not found']);
        }
        return redirect()->route('events.index')
                        ->with('warnings','Event not found');
    }

    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $validator = Validator::make($request->all(),[
            'name'=>'required',
            'place'=>'required',
            'date'=>'required'
        ]);
        if($validator->fails()){
            return redirect('events/create')
                            ->withErrors($validator)
                            ->withInput();
        }

        $newEvent = Event::create([
            'name'=>$request->input('name'),
            'place'=>$request->input('place'),
            'date'=>$request->input('date'),
            'user_id'=>Auth::user()->id
        ]);

        if($newEvent){
            return redirect()->route('events.index')
                            ->with('success','Added new Event');
        }

        return back()->withInput()->with('error','Error');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function show(Event $event)
    {
        //
        if(Auth::user()->id == $event->user_id)
        {
            $event = Event::find($event->id);
            $eventcandidates = Event::find($event->id)
                                    ->candidates()
                                    ->orderBy('number')
                                    ->get();
            
            $availcandidates = Candidate::whereNotIn('id',$eventcandidates->pluck('id'))
                            ->orderBy('lastname')
                            ->get();
                            
            $categories = Category::whereNotIn('id',Event::find($event->id)
                                                        ->categories()
                                                        ->select('categories.id as id')
                                                        ->pluck('id'))
                                    ->orderBy('name')
                                    ->get();
            $criterias = Criteria::where('percent',5)->get();
            $users = User::where('role_id',3)->get();

            $event2 = DB::table('events')
                        ->join('category_event','events.id','=','category_event.event_id')
                        ->join('categories','category_event.category_id','=','categories.id')
                        ->join('event_category_criteria','categories.id','=','event_category_criteria.category_id')
                        ->join('criterias','event_category_criteria.criteria_id','=','criterias.id')
                        ->where('event_category_criteria.event_id','=',$event->id)                    
                        ->where('events.id','=',$event->id)
                        ->orderBy('categories.name')
                        ->select('*','categories.name as category_name','criterias.id as criteria_id','criterias.name as criteria_name','criterias.percent','categories.id as category_id')
                        ->get();

            return view('events.show',[
                'events'=>$event,
                'candidates'=>$availcandidates,
                'programs'=>$event2,
                'categories'=>$categories,
                'criterias'=>$criterias,
                'users'=>$users,
                'eventcandidates'=>$eventcandidates,
                'judge'=>User::whereNotIn('id',$event->users()->pluck('users.id'))
                                ->where('role_id',3)
                                ->get(),
                'selectedjudge'=>$event->users()->orderBy('judge_number')->get()
            ]);
        }
        return redirect()->route('events.index')
                        ->with('warnings','Event not found');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function edit(Event $event)
    {
        //
        $event = Event::find($event->id);
        return view('events.edit',['events'=>$event]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Event $event)
    {
        //
        $event = Event::where('id',$event->id)
                        ->update([
                            'name'=>$request->input('name'),
                            'place'=>$request->input('place'),
                            'date'=>$request->input('date')
                        ]); 

        //pivot
        // $event->category()->sync([1,2,3])
        //removes all rows with 1,2,3 ids
        if($event){
            return redirect()->route('events.show',['events'=>$event])
                            ->with('success','Event Updated');
        }
        return back()->withInput()->with('errors','Error Updating');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Event  $event
     * @return \Illuminate\Http\Response
     */
    public function destroy(Event $event)
    {
        //
        $event = Event::find($event->id);
        $events = Event::all();
        if($event->delete()){
            return redirect()->route('events.index')
                    ->with('success','Event removed');
        }
        return redirect()->route('events.index')
                ->with('errors','Error removing event');
    }

   


    // Return view of ajax/judge (update/add)

     public function returnView(Request $request){

        $event = Event::find($request->event_id);
        $user = $event->users()->where('users.id',$request->judge_id)->first();
        
        return view('events.judge.ajaxnew',['event'=>$event,'user'=>$user]);
    } 

    // MEthod for initial setup 

    public function setup(Request $request,$event_id){

        $event = Event::find($event_id);
        $segments = Segments::all();
        $judge = $event->users()->orderBy('judge_number')->get();
        $halfwayCategories = [];

        // check if event is valid and is user's
        if(!$event || !User::find(Auth::user()->id)->events()->where('events.id',$event_id)->first()){
            return redirect()->route('events.index')->with('warnings','Event not found');
        }

        $categoryCriteriasTotal = DB::table('events')
                        ->select(DB::raw('categories.name, categories.id ,SUM(criterias.percent) as sum'))                                
                        ->join('category_event','events.id','=','category_event.event_id')
                        ->join('categories','category_event.category_id','=','categories.id')
                        ->join('event_category_criteria','categories.id','=','event_category_criteria.category_id')
                        ->join('criterias','event_category_criteria.criteria_id','=','criterias.id')
                        ->where('event_category_criteria.event_id','=',$event->id)                    
                        ->where('events.id','=',$event->id)
                        ->groupBy('event_category_criteria.event_id','event_category_criteria.category_id')
                        ->get();

        // get categories where sum < 100
        foreach ($categoryCriteriasTotal as $key => $values) {
            if($values->sum < 100){
                $halfwayCategories[] = $values->name;
            }
        }

        // if 1 category criteria total percent < 100 return with warning
        
        if(count($halfwayCategories)){
            $message = '';
            for($i = 0; $i < count($halfwayCategories); $i++){
                $message.=$halfwayCategories[$i] . ' total criteria percentage must have a total of 100% <br>';
            }
            return redirect("events/{$event->id}/#categories")->with('warnings',$message);
        }

        // else continue setup // select number segments

        return view('events.continue.index',['judges'=>$judge,'event'=>$event,'segments'=>$segments]);   
        
    }

    // Method for setup after checking criterias

    public function setupsegment(Request $request,$event_id,$segment = null){

        $event = Event::find($event_id);
        $numberOfSegment = $request->input('segment');
        $categories = null;
        $segment = ($segment == null ) ? Segments::find(1) : Segments::find(Crypt::decrypt($segment));
        $isRecentReset = true;
        $segmentPercentTotal = null;
        
        // Remove old saved data where segment number > current segment
         SegmentCategory::where('event_id',$event->id)
                            ->where('segment_id','>',$segment->id)
                            ->delete();
       
        // check if event is valid and is user's
        if(!$event || !User::find(Auth::user()->id)->events()->where('events.id',$event_id)->first()){
            return redirect()->route('events.index')->with('warnings','Event not found');
        }


        // IF POST FROM SELECTION OF CATEGORIES ON EVENT SEGMENT
        if($request->input('categories')):

            // Remove old saved data
            SegmentCategory::where('event_id',$event->id)
                            ->where('segment_id',$segment->id)
                            ->delete();

            // If selected category is only 1.
            if(count($request->input('categories') ) == 1):
                
                $category = Category::find(Crypt::decrypt($request->input('categories')[0]));
                // Check if category is invalid
                if(!$category):
                    // Remove partially saved data if category is not found 
                    SegmentCategory::where('event_id',$event->id)
                                    ->where('segment_id',$segment_id)
                                    ->delete();

                    return back()->withInput()->with('warnings','Category not found');  
                endif;
                // Else Insert 1 Category
                // SegmentCategory::create([
                //     'event_id'=>$event->id,
                //     'segment_id'=>$segment->id,
                //     'category_id'=>$category->id,
                //     'percent'=>100
                // ]);

                 // Update EventSegment to avoid duplicate
                EventSegment::where('event_id',$event->id)
                    ->where('segment_id',$segment->id)
                    ->update([
                        'lemet'=>$request->input('lemet')??null ,
                        'reset'=>($request->input('reset') == true) ? 1 : 0 ,
                        'percent'=>$request->input('percent')??null
                ]);

                // GET NUMBER OF EVENT SEGMENT
                $eventSegment = EventSegment::where('event_id',$event->id)->count();
                $nextSegments = EventSegment::where('segment_id','>',$segment->id)->get();
                $lastSegment = false;

                if(count($nextSegments) == 1):
                    $lastSegment = true;
                endif; 

                // view for setting up category percentage per segment
                return view('events.continue.setupcategories',[
                    'isLastSegment'=>$lastSegment,
                    'totalSegment'=>$eventSegment,
                    'segment'=>$segment,
                    'categories'=>$category,
                    'event'=>$event
                ]);
                

            // else if selected category > 1            
            elseif(count($request->input('categories') ) > 1):

                // Remove old data to avoid duplicate
                SegmentCategory::where('event_id',$event->id)
                                ->where('segment_id',$segment->id)
                                ->delete();

                $category_ids = []; // used to store selected categories

                for($i = 0; $i < count($request->input('categories')); $i++):
                    $category = Category::find(Crypt::decrypt($request->input('categories')[$i]));
                    // check if category is invalid
                    if(!$category):
                        // Remove partially saved data
                        SegmentCategory::where('event_id',$event->id)
                                        ->where('segment_id',$segment_id)
                                        ->delete();
                        // return to page
                        return back()->withInput()->with('warnings','Category not found');  
                    endif;
                    // get id of selected categories
                    $category_ids[] = $category->id;
                    // Store to database selected categories for this segment
                    SegmentCategory::create([
                        'event_id'=>$event->id,
                        'segment_id'=>$segment->id,
                        'category_id'=>$category->id
                    ]); 
                endfor;
                
                // Get selected categories
                $categories = Category::whereIn('id',$category_ids)->get();
                // Update EventSegment to avoid duplicate
                EventSegment::where('event_id',$event->id)
                    ->where('segment_id',$segment->id)
                    ->update([
                        'lemet'=>$request->input('lemet')??null ,
                        'reset'=>($request->input('reset') == true) ? 1 : 0 ,
                        'percent'=>$request->input('percent')??null
                ]);

                // GET NUMBER OF EVENT SEGMENT
                $eventSegment = EventSegment::where('event_id',$event->id)->count();
                $nextSegments = EventSegment::where('segment_id','>',$segment->id)->get();
                $lastSegment = false;

                if(count($nextSegments) == 1):
                    $lastSegment = true;
                endif; 
                // view for setting up category percentage per segment
                return view('events.continue.setupcategories',['isLastSegment'=>$lastSegment,'totalSegment'=>$eventSegment,'segment'=>$segment,'categories'=>$categories,'event'=>$event]);
                
            endif;

        
        // If POST FROM SEGMENT NUMBER SELECTION
        else:
            
            // INSERT how many segments selected
            
            // remove old data to avoid duplicate
            EventSegment::where('event_id',$event->id)->delete();
            
            for($i = 1; $i<=$numberOfSegment; $i++):
                $eventSegment = EventSegment::create([
                        'event_id'=>$event->id,
                        'segment_id'=>$i
                    ]);
                endfor;

            $categories = $event->categories()->orderBy('categories.name')->get();

            $eventSegment = $numberOfSegment;

            if($eventSegment):
                return view('events.continue.setup',[
                    'isRecentReset'=>$isRecentReset,
                    'segmentPercentTotal'=>$segmentPercentTotal,
                    'categories'=>$categories,
                    'totalSegment'=>$eventSegment,
                    'segment'=>$segment,
                    'event'=>$event
                ]);   
            endif;
        endif;
    }

    public function setupSegmentCategory(Request $request, $event_id, $segment_number){

        $event = Event::find(Crypt::decrypt($event_id));
        $segment = Segments::find(Crypt::decrypt($segment_number));
        $eventSegments = null;
        $isRecentReset = true;
        $segmentPercentTotal = null;

        // remove partially saved data. Avoid duplicating data on page refresh/ Resubmit
        SegmentCategory::where('event_id',$event->id)->where('segment_id',$segment->id)->delete();
        

        // check if event is valid and is user's
        if(!$event || !User::find(Auth::user()->id)->events($event->id)->first() ){
            return redirect()->route('events.index')->with('warnings','Event not found');
        }

        // check if segment is valid and event has this segment
        if(!$segment || !EventSegment::where('event_id',$event->id)->where('segment_id',$segment->id)->first()){
            return redirect()->route('events.show',[$event->id])->with('warnings','Segment not found');                
        }

        // check if total percentage not equal to 100
        if(array_sum($request->input('percent')) != 100){
            return back()->withInput()->with('warnings','Total percentage must be 100%');
        };

        // insert category percentage to database
        foreach($request->input('percent') as $category=>$percent)
        {
            Category::find($category);
            // check if category is valid
            if($category){
                SegmentCategory::create([
                'event_id'=>$event->id,
                'segment_id'=>$segment->id,
                'category_id'=>$category,
                'percent'=>$percent
                ]);
            }else{
                // else if category not found , removed currently saved data on event's segment and return
                SegmentCategory::where('event_id',$event->id)->where('segment_id',$segment->id)->delete();
                return back()->withInput()->with('warnings','Category not found');
            }
        }

        $eventSegment = EventSegment::where('event_id',$event->id)->count();
        $nextSegments = EventSegment::where('segment_id','>',$segment->id)->get();
       


        // CHECK IF THERE ARE STILL NEXT SEGMENTS TO FILL
        if(count($nextSegments)>0):
            $nextSegment = Segments::find($nextSegments[0]->segment_id);
            $lastSegment = false;
            $isReset = true;

            
            if(count($nextSegments) == 1):
                $lastSegment = true;
            endif;  

            $currentSegment = EventSegment::where('segment_id',$segment->id)->first(); 
            $segmentPercentTotal = EventSegment::where('segment_id','<',$nextSegment->id)
                                        ->where('reset',0)
                                        ->sum('percent');
            if($currentSegment->reset == 0){
                $isRecentReset = false;
            }

            $selectedcategories = SegmentCategory::where('event_id',$event->id)->pluck('category_id');
            $unselectedcategories = $event->categories()->whereNotIn('categories.id',$selectedcategories)->get();

            return view('events.continue.setup',['segmentPercentTotal'=>$segmentPercentTotal,'isRecentReset'=>$isRecentReset,'categories'=>$unselectedcategories,'totalSegment'=>$eventSegment,'segment'=>$nextSegment,'event'=>$event,'isLastSegment'=>$lastSegment]);   
        endif;



        return view();
       }
}
