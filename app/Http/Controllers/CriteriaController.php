<?php

namespace App\Http\Controllers;

use App\Criteria;
use Illuminate\Http\Request;
use App\Category;
use Validator;
use DB;
use App\Event;
use App\Program;
use Illuminate\Support\Facades\Auth;
use App\CategoryEvent;
use Response;

class CriteriaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $criterias = Criteria::where('percent',5)->get();
        return view('criterias.index',['criterias'=>$criterias]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($category_id = null)
    {
        //
        $categories = null;
        if($category_id){
            $categories = Category::find($category_id);
        }

        return view('criterias.create',['category_id'=>$category_id,'categories'=>$categories]);
    }

    public function editcriteria(Request $request,$category_id, $event_id, $criteria_id){
        $category = Category::find($category_id);
        $event = Event::find($event_id);
        $criteria = Criteria::find($criteria_id);
        $criterias = Criteria::where('percent',5)->get();    
        
        $totalpercentage = DB::table('events')
            ->join('category_event','events.id','=','category_event.event_id')
            ->join('categories','category_event.category_id','=','categories.id')
            ->join('event_category_criteria','categories.id','=','event_category_criteria.category_id')
            ->join('criterias','event_category_criteria.criteria_id','=','criterias.id')
            ->where('event_category_criteria.event_id','=',$event->id) 
            ->where('categories.id', '=', $category->id)                   
            ->where('events.id','=',$event->id)
            ->sum('percent');

        if($request->ajax()){
            return response(['criterias'=>$criterias,
                            'event'=>$event,
                            'category'=>$category,
                            'criteria_old'=>$criteria,
                            'total_percentage'=>$totalpercentage
                            ]);
        }
        return view('events.criterias.edit',[
                                                'criterias'=>$criterias,
                                                'event'=>$event,
                                                'category'=>$category,
                                                'criteria_old'=>$criteria,
                                                'total_percentage'=>$totalpercentage
                                            ]);
    }

    public function updatecriteria(Request $request,$criteria_id, $category_id, $event_id){
       
        $validator = Validator::make($request->all(),[
            'name'=>'required',
            'event_id'=>'required',
            'category_id'=>'required',
            'percent'=>'required|max:'.$request->maxpercent
        ],[
            'percent.max'=>'Percentage exceeded',
            'name.required'=>'Criteria not found',
            'event_id.required'=>'Event not found',
            'category_id.required'=>'Category not found',
            'percent.required'=>'Input criteria percentage'
        ]);

        if($validator->fails()){
            if($request->ajax()){
                return Response::json(array('errors'=> $validator->getMessageBag()->toarray()));
            }
        }

        if($request->input('percent') > 95){
            if($request->ajax()){
                return response(['warnings'=>'Percentage must not be greater than 95']);
            }
        }

        $validator = Validator::make($request->all(),[
            'name'=>'required|regex:/[a-z\s]+/'
        ]);

        $userevent = Event::where('id',$event_id)
                            ->where('user_id',Auth::user()->id)
                            ->first();

        $event = Event::find($event_id);
        $category = Category::find($category_id);

        $event_category_criteria = Program::where('event_id',$event_id)
                                            ->where('category_id',$category_id)
                                            ->where('criteria_id',$criteria_id)
                                            ->first();

        $criteria = Criteria::where('name',$request->input('name'))
                        ->where('percent',$request->input('percent'))
                        ->first();
        $oldcriteria = Criteria::find($criteria_id);

        if($criteria==null){
            if($request->ajax()){
                return response(['warnings'=>'Criteria name not found']);
            }
            return redirect("/events/{{$event->id}}")
                        ->with('warnings','Criteria not found');
        }

        if(!$event || !$userevent || !$event_category_criteria){
            if($request->ajax()){
                return response(['warnings','Event not found','event'=>$event,'userevent'=>$userevent,'ecc'=>$event_category_criteria]);
            };
            return redirect()->route('events.index')
                            ->with('warnings','Event not found');
        }

        // $checkcriteria = Program::where('event_id',$event_id)
        //                         ->where('category_id',$category_id)
        //                         ->where('criteria_id',$criteria->id)
        //                         ->first();

        // if($checkcriteria){
        //     return redirect("/events/{$event->id}")
        //                     ->with('success','Error updating. Selected criteria already added');
        // }

        $totalpercentage = DB::table('events')
                    ->join('category_event','events.id','=','category_event.event_id')
                    ->join('categories','category_event.category_id','=','categories.id')
                    ->join('event_category_criteria','categories.id','=','event_category_criteria.category_id')
                    ->join('criterias','event_category_criteria.criteria_id','=','criterias.id')
                    ->where('event_category_criteria.event_id','=',$event->id) 
                    ->where('categories.id', '=', $category->id)                   
                    ->where('events.id','=',$event->id)
                    ->sum('percent');

        if( $request->input('percent') > ( (100 - $totalpercentage) + $oldcriteria->percent ) ){
            
            if($request->ajax()){
                return response(['warnings'=>'Percentage exceeded','percent'=>$request->input('percent'),'total'=>$totalpercentage  ]);
            }
        }

        $updatecriteria = Program::where('event_id',$event_id)
                                    ->where('category_id',$category_id)
                                    ->where('criteria_id',$criteria_id)
                                    ->update([
                                        'criteria_id'=> $criteria->id
                                    ]);
        if($updatecriteria){

            $program = DB::table('events')
                    ->join('category_event','events.id','=','category_event.event_id')
                    ->join('categories','category_event.category_id','=','categories.id')
                    ->join('event_category_criteria','categories.id','=','event_category_criteria.category_id')
                    ->join('criterias','event_category_criteria.criteria_id','=','criterias.id')
                    ->where('category_event.category_id','=',$category_id)
                    ->where('event_category_criteria.criteria_id','=',$criteria->id)
                    ->where('event_category_criteria.event_id','=',$event->id)                    
                    ->where('events.id','=',$event->id)
                    ->select('events.id as event_id','criterias.id as criteria_id','criterias.name as criteria_name','criterias.percent as percent','categories.name as category_name','categories.id as category_id')
                    ->first();

            if($request->ajax()){
                return response(['success'=>'Criteria Updated','program'=>$program,'old_criteria_id'=>$criteria_id]);
            }
            return redirect()->route('events.show',['events'=>$event,'programs'=>$program])
                                ->with('success','Criteria for the event has been updated');
        }
        if($request->ajax()){
            return response(['errors','An error has occurred']);
        }
        return redirect()->route('events.show',['events'=>$event,'programs'=>$program])
                            ->with('errors','An error has occurred');

    }

    public function addcriteria(Request $request,$category_id, $event_id){
        
        $selectedcriterias = DB::table('event_category_criteria')
                                ->where('category_id',$category_id)
                                ->where('event_id',$event_id)
                                ->join('criterias','criterias.id','=','event_category_criteria.criteria_id')
                                ->pluck('name');

        $criterias = Criteria::where('percent',5)
                                    ->whereNotIn('name',$selectedcriterias)
                                    ->get();
        
  
        $categories = Category::find($category_id);     
        $events = Event::find($event_id);

        $totalpercentage = DB::table('events')
            ->join('category_event','events.id','=','category_event.event_id')
            ->join('categories','category_event.category_id','=','categories.id')
            ->join('event_category_criteria','categories.id','=','event_category_criteria.category_id')
            ->join('criterias','event_category_criteria.criteria_id','=','criterias.id')
            ->where('event_category_criteria.event_id','=',$events->id) 
            ->where('categories.id', '=', $categories->id)                   
            ->where('events.id','=',$events->id)
            ->sum('percent');


        if($request->ajax()){
            return response([
                                'criterias'=>$criterias,
                                'category_id'=>$category_id,
                                'event'=>$events,
                                'event_id'=>$event_id,
                                'category'=>$categories,
                                'total_percentage'=>$totalpercentage
                            ]);
        }

        return view('events.criterias.create',[
                                        'criterias'=>$criterias,
                                        'category_id'=>$category_id,
                                        'event'=>$events,
                                        'event_id'=>$event_id,
                                        'category'=>$categories,
                                        'total_percentage'=>$totalpercentage
                                        ]);
    }

    public function storecriteria(Request $request,$category_id, $event_id){
        

        $event = Event::find($request->input('event_id'));
        $category = Category::find($request->input('category_id'));

        $criteria = Criteria::where('name',$request->input('name'))
                            ->where('percent',$request->input('percent'))
                            ->first();

        $criterias = DB::table('criterias')
                        ->where('name',$request->input('name'))
                        ->pluck('id');
        
        $checkcriteria = Program::wherein('criteria_id',$criterias)
                    ->where('category_id',$category->id)
                    ->where('event_id',$event->id)->first();

        $totalpercentage = DB::table('events')
                    ->join('category_event','events.id','=','category_event.event_id')
                    ->join('categories','category_event.category_id','=','categories.id')
                    ->join('event_category_criteria','categories.id','=','event_category_criteria.category_id')
                    ->join('criterias','event_category_criteria.criteria_id','=','criterias.id')
                    ->where('event_category_criteria.event_id','=',$event->id) 
                    ->where('categories.id', '=', $category->id)                   
                    ->where('events.id','=',$event->id)
                    ->sum('percent');

        
        if($request->input('percent') > 95){
             if($request->ajax()){
                return response(['warnings'=>'Percentage must not exceed 95%']);
            }
           return back()->withInput()->with('warnings','Percentage must not exceed 95%');
        }
        if(!$criteria){
            if($request->ajax()){
                return response(['warnings'=>'Criteria not found']);
            }
            return redirect("/criterias/category/{$request->input('category_id')}/event/{$request->input('event_id')}")
            ->with('warnings',"Criteria not found");
        }
        if($checkcriteria){
            if($request->ajax()){
                return response(['warnings'=>'Criteria already added']);
            }
            return redirect("/criterias/category/{$request->input('category_id')}/event/{$request->input('event_id')}")
            ->with('warnings',"Criteria already added");
        }

        //  $program = DB::table('events')
        //                 ->join('category_event','events.id','=','category_event.event_id')
        //                 ->join('categories','category_event.category_id','=','categories.id')
        //                 ->join('event_category_criteria','categories.id','=','event_category_criteria.category_id')
        //                 ->join('criterias','event_category_criteria.criteria_id','=','criterias.id')
        //                 ->where('category_event.category_id','=',$category->id)                                                
        //                 ->where('event_category_criteria.criteria_id','=',$criteria->id)
        //                 ->where('event_category_criteria.event_id','=',$event->id)                    
        //                 ->where('events.id','=',$event->id)
        //                 ->orderBy('categories.name')
        //                 ->select('events.id as event_id','categories.name as category_name','criterias.id as criteria_id','criterias.name as criteria_name','criterias.percent as percent','categories.id as category_id')
        //                 ->first();

        if(($request->input('percent') + $totalpercentage) > 100){
            if($request->ajax()){
                return response(['warnings'=>'Percentage exceeded']);
            }
        }

        $addnewcriteria = Program::create([
            'event_id'=> $request->input('event_id'),
            'category_id'=> $request->input('category_id'),
            'criteria_id'=> $criteria->id
        ]);
        if($addnewcriteria){

            if($request->ajax()){
                return response(['success'=>'Added criteria','criteria'=>$criteria,'event'=>$event,'category'=>$category]);
            }
            return redirect("/criterias/category/{$request->input('category_id')}/event/{$request->input('event_id')}")
                            ->with('success',"Added {$request->name} Criteria to category");
        }
        if($request->ajax()){
                return response(['errors'=>'Error adding criteria']);
            }
        return redirect()->route('events.show',['events'=>$event])
                                ->with('errors',"Error creating criteria");
        
        
    }

    public function removecriteria(Request $request, $criteria_id,$category_id,$event_id)
    {
        $event_category_criteria = Program::where('category_id',$category_id)
                                        ->where('event_id',$event_id)
                                        ->where('criteria_id',$criteria_id)
                                        ->first();

       

        $event_category = CategoryEvent::where('category_id',$category_id)
                                        ->where('event_id',$event_id)
                                        ->first();

        if($event_category_criteria->delete()){

            // Check if category has no criteria then delete;

             $event_category_criteria_search = Program::where('category_id',$category_id)
                                        ->where('event_id',$event_id)
                                        ->first();

            if(!$event_category_criteria_search){
                $event_category->delete();
            }

            if($request->ajax()){
                return response(['success'=>'Criteria removed']);
            }
            return redirect("/events/{$event_id}")
                        ->with('success','Criteria removed');
        }

         if($request->ajax()){
                        return response(['warnings'=>'Program not found']);
                    }
        return back()->with('success','Program not found');
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
            'name'=>'required'
        ]);

        if($validator->fails()){
            return redirect('/criterias/create')
                    ->withErrors($validator)
                    ->withInput();
        }

        $criteria = Criteria::where('name',$request->input('name'))->first();
        if($criteria){

            return redirect()->route('criterias.index')
            ->with('warning'," Criteria already created");

        }

        for($count = 5; $count<=95; $count+=5):
            $newCriteria = Criteria::create([
                'name'=>$request->input('name'),
                'percent'=>$count
            ]);
        endfor;
        
        if($newCriteria){
            return redirect()->route('criterias.index')
                            ->with('success',"Created {$request->input('name')} Criteria");
        }
        return redirect()->route('criterias.index')
                                ->with('errors',"Error creating criteria");
        
        
        


    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Criteria  $criteria
     * @return \Illuminate\Http\Response
     */
    public function show(Criteria $criteria)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Criteria  $criteria
     * @return \Illuminate\Http\Response
     */
    public function edit(Criteria $criteria)
    {
        //
        $criteria = Criteria::find($criteria->id);
        return view('criterias.edit',['criterias'=>$criteria]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Criteria  $criteria
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Criteria $criteria)
    {
        //
        $criteria = Criteria::where('name',$criteria->name)
                                ->update([
                                    'name'=>$request->input('name')
                                ]);
        if($criteria){
            return redirect()->route('criterias.index')
                            ->with('success','Criteria Updated');
        }
        return redirect()->route('criterias.index')
                            ->with('errors','Error updating criteria');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Criteria  $criteria
     * @return \Illuminate\Http\Response
     */
    public function destroy(Criteria $criteria)
    {
        //
    }

     public function returnView(Request $request){

        $category = Category::find($request->category_id);
        $criteria = Criteria::find($request->criteria_id);
        $event = Event::find($request->event_id);

        $program = DB::table('events')
                        ->join('category_event','events.id','=','category_event.event_id')
                        ->join('categories','category_event.category_id','=','categories.id')
                        ->join('event_category_criteria','categories.id','=','event_category_criteria.category_id')
                        ->join('criterias','event_category_criteria.criteria_id','=','criterias.id')
                        ->where('category_event.category_id','=',$category->id)                                                
                        ->where('event_category_criteria.criteria_id','=',$criteria->id)
                        ->where('event_category_criteria.event_id','=',$event->id)                    
                        ->where('events.id','=',$event->id)
                        ->orderBy('categories.name')
                        ->select('events.id as event_id','categories.name as category_name','criterias.id as criteria_id','criterias.name as criteria_name','criterias.percent as percent','categories.id as category_id')
                        ->first();

        return view('events.criterias.ajaxupdate',['category'=>$category,'event'=>$event,'criteria'=>$criteria,'program'=>$program]);
    }

    public function returnNewCritView(Request $request){

        $category = Category::find($request->category_id);
        $event = Event::find($request->event_id);
        $criteria = Criteria::find($request->criteria_id);

         $program = DB::table('events')
                        ->join('category_event','events.id','=','category_event.event_id')
                        ->join('categories','category_event.category_id','=','categories.id')
                        ->join('event_category_criteria','categories.id','=','event_category_criteria.category_id')
                        ->join('criterias','event_category_criteria.criteria_id','=','criterias.id')
                        ->where('category_event.category_id','=',$category->id)                                                
                        ->where('event_category_criteria.criteria_id','=',$criteria->id)
                        ->where('event_category_criteria.event_id','=',$event->id)                    
                        ->where('events.id','=',$event->id)
                        ->orderBy('categories.name')
                        ->select('events.id as event_id','categories.name as category_name','criterias.id as criteria_id','criterias.name as criteria_name','criterias.percent as percent','categories.id as category_id')
                        ->first();

        return view('events.criterias.ajaxupdate',['category'=>$category,'event'=>$event,'criteria'=>$criteria,'program'=>$program]);
    }
}
