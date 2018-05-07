<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;
use Validator;
use App\Event;
use App\CategoryEvent;
use App\Criteria;
use App\Program;
use Illuminate\Support\Facades\Auth;
use Response;
use App\User;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $categories = Category::all();
        return view('categories.index',['categories'=>$categories]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($event_id = null)
    {
        //
        $events = null;
        $categories = Category::all();
        $criterias = Criteria::where('percent',5)->get();
        if($event_id){
            $events = Event::find($event_id);
            return view('events.categories.create',['events'=>$events,'categories'=>$categories,'criterias'=>$criterias]);
        }
        return view('categories.create',['events'=>$events,'categories'=>$categories,'criterias'=>$criterias]);
    }






    /************************ A       D        D     CATEGORY ON EVENT ***********************/
  
    




    public function addcategory(Request $request)
    {
        
        $category = Category::where('id',$request->input('category_id'))->first();
        $event = Event::where('id',$request->input('event_id'))->first();
        $criteria = null;
        $criterias = Criteria::select('name')->distinct()->get();


        $validator = Validator::make($request->all(),[
            'category_id'=>'required|integer',
            'criteria_name'=>"required|notIn:{$criterias}",
            'percent'=>'required|integer'
        ],[
            'category_id.integer'=>'Category not valid, reload page',
            'category_id.required'=>'Select category',
            'criteria_name.notIn'=>'Criteria not valid, reload page',
            'criteria_name.required'=>'Select criteria',
            'percent.required'=>'Input Criteria percentage'
        ]);

        if($validator->fails()){
            if($request->ajax()){
                return Response::json(array('errors'=> $validator->getMessageBag()->toarray()));
            }
        }


        // check if event is valid and is user's
         if(!$event){
            if($request->ajax()){
                return response(['warnings'=>'Event not found']);
            }
           return redirect()->route('events.index')
                            ->with('warnings','Event not found');
        }

        $userevent = Event::where('id',$event->id)
                        ->where('user_id',Auth::user()->id)
                        ->first();

        if(!$userevent){
            if($request->ajax()){
                return response(['warnings'=>'Event not found']);
            }
           return redirect()->route('events.index')
                            ->with('warnings','Event not found');
        }

        $categoryEvent = CategoryEvent::where('category_id',$category->id)
                                ->where('event_id',$event->id)
                                ->first();
        // $categoryEvent = $event->categories->where('category_id',$category->id)->first();

        $eventCategoryCriteria = Program::where('event_id',$request->input('event_id'))
                                ->where('category_id',$request->input('category_id'))
                                ->where('criteria_id',$request->input('criteria_id'))
                                ->first();

        // get criteria id base on name and percentage
        $criteria = Criteria::where('name',$request->input('criteria_name'))
                                ->where('percent',$request->input('percent'))
                                ->first();

        if($request->input('percent') > 95){
            if($request->ajax()){
                return response(['warnings'=>'Percentage must not exceed 95%']);
            }
            return back()->withInput()->with('warnings','Percentage must not exceed 95%');
        }

        // check if criteria exists
        if(!$criteria){
            if($request->ajax()){
                return response(['warnings'=>'Criteria not found']);
            }
            return redirect("/categories/create/{$event->id}")
                            ->with('warnings','Criteria not found');
        }
        // check if category is already added to event
        if($categoryEvent){
            if($request->ajax()){
                return response(['warnings'=>'Category already added']);
            }
            return redirect("/categories/create/{$event->id}")
                            ->with('warnings','Category already added');
        }

        
        // if(!$eventCategoryCriteria){
                Program::create([
                    'event_id'=>$request->input('event_id'),
                    'category_id'=>$request->input('category_id'),
                    'criteria_id'=>$criteria->id
                ]);
                $event->categories()->attach($category);
                if($request->ajax()){
                    return response(['success'=>'Added new category to event','category'=>$category,'event'=>$event,'criteria'=>$criteria]); 
                }

                return redirect("/events/{$event->id}")
                            ->with('success','Added new category to event');
        // }else{
        //     return redirect("/categories/create/{$event->id}")
        //                     ->with('success','Category already added');
        // }

        return response(['warnings'=>'Error event/category not found']);

        return redirect("/events/$request->input('event_id')")
                ->with('warnings','Error event/category not found');
    }

    public function editcategory(Request $request,$category_id,$event_id)
    {
        $eventcategories = Event::find($event_id)->categories->pluck('id');
        $event = Event::find($event_id);
        $categories = Category::whereNotIn('id',$eventcategories)->get();
        $category = Category::find($category_id);

        if($request->ajax()){
            return Response::json(array('success'=>'Data Category Fetched','event'=>$event,'categories'=>$categories,'category'=>$category));
        }
        return view('events.categories.edit',['event'=>$event,'categories'=>$categories,'category'=>$category]);
    }






    /************************ U     P     D    A    T   E     CATEGORY ON EVENT ***********************/

    




    public function updatecategory(Request $request,$category_id,$event_id)
    {
        $category = Category::find($category_id);
        $event = Event::find($event_id);
        $eventniuser = Event::where('id',$event_id)
                        ->where('user_id',Auth::user()->id)
                        ->first();
        $oldcat = Category::find($request->input('old_category_id'));

        if(!$eventniuser){
            if($request->ajax()){
                return response(['warnings'=>'Error updating event not found']);
            }
            return redirect()->route('events.index')
                                ->with('warnings','Error updating event not found');

        }
        if($category && $event){

            $categoryEvent = CategoryEvent::where('category_id',$request->input('category_id'))
                                ->where('event_id',$event->id)
                                ->first();

            if($categoryEvent){
                if($request->ajax()){
                    return response(['warnings'=>'Cant update category. Selected category already added','category'=>$categoryEvent]);
                }

                return redirect("/events/{$event->id}")
                ->with('warnings','Cant update category. Selected category already added');
            }
            $event_category_criteria = Program::where('category_id',$category_id)
                                                ->where('event_id',$event_id)
                                                ->update([
                                                    'category_id'=>$request->input('category_id'),
                                                    'event_id'=>$request->input('event_id')
                                                ]);

            $event->categories()->updateExistingPivot($category->id,[
                    'category_id'=>$request->input('category_id'),
                    'event_id'=>$request->input('event_id')
            ]);
            
            if($request->ajax()){
                $category = Category::find($request->input('category_id'));
                return response(['success'=>'Category updated','category'=>$category,'event'=>$event,'old'=>$oldcat]);
            }
            return redirect()->route('events.show',['events'=>$event])
                            ->with('success','Category updated');
        }   

         if($request->ajax()){
                return response(['warnings'=>'Category/Event not found']);
        }

        return redirect()->route('events.show',['events'=>$event])
                        ->with('warnings','Category/Event not found');
    }



    /************************ R     E     M     O     V     E    CATEGORY ON EVENT ***********************/





    public function removecategory(Request $request,$category_id,$event_id){
        $eventniuser = Event::where('id',$event_id)
                ->where('user_id',Auth::user()->id)
                ->first();
        $category = Category::find($category_id);
        if(!$eventniuser){
            if($request->ajax()){
                return response(['warnings'=>'Error updating, Event not found']);
            }
            return redirect()->route('events.index')
                            ->with('warnings','Error updating event not found');
        }
        $categoryevent = CategoryEvent::where('category_id',$category_id)
                                        ->where('event_id',$event_id);
        $event_category_criteria = Program::where('category_id',$category_id)
                                        ->where('event_id',$event_id);
        $event = Event::find($event_id);
        if($categoryevent->delete() && $event_category_criteria->delete()){
            if($request->ajax()){
                return response(['success'=>'Category Removed','category'=>$category]);
            }
            return redirect()->route('events.show',['events'=>$event])
                                ->with('success','Category Removed');
        }

          if($request->ajax()){
                return response(['warnings','Error Removing category']);
            }
        return redirect()->route('events.show',['events'=>$event])
                            ->with('errors','Error Removing category');
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
            'name'=>'required|regex:/[a-z\s]+/'
        ]);

        if($validator->fails()){
            return back()
                    ->withErrors($validator)
                    ->withInput();
        }

        $newCategory = Category::create([
            'name'=>$request->input('name')
        ]);

        if($newCategory){
            return redirect()->route('categories.index')
                            ->with('success','Successfully created new Category');
        }

        return redirect()->route('categories.index')
                            ->with('error','Error creating new category');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        //
        $category = Category::find($category->id);
        $events = null;
        return view('categories.edit',['categories'=>$category,'event'=>null]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        //
        $categories = Category::where('id',$category->id)
                                ->update([
                                    'name'=>$request->input('name')
                                ]);

        if($categories){
            return redirect()->route('categories.index')
                                ->with('success',"Updated {$request->input('name')} category ");
        }

        return back()->withInput();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy(Category $category)
    {
        //
        $category = Category::find($category->id);
        if($category->delete()){
            return redirect()->route('categories.index')
                            ->with('success',ucfirst($category->name) . " category has been removed");
        }
        return redirect()->route('categories.index')
                            ->with('errors','Error removing category');
    }





    // VIEWS //

     public function returnView(Request $request){

        $category = Category::find($request->category_id);

        $event = Event::find($request->event_id);

        return view('events.categories.ajaxnewcategory',['category'=>$category,'event'=>$event]);
    }

    public function returnNewCatView(Request $request){

        $category = Category::find($request->category_id);

        $event = Event::find($request->event_id);
        $criteria = Criteria::find($request->criteria_id);

        return view('events.categories.ajaxnew',['category'=>$category,'event'=>$event,'criteria'=>$criteria]);
    }
}
