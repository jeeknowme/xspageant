<?php

namespace App\Http\Controllers;

use App\Candidate;
use Validator;
use App\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\CandidateEvent;
use Response;
use App\Http\Requests\StoreFormValidation;

class CandidateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $candidates = Candidate::all();
        $candidatesMale = Candidate::where('gender','male')->get() ;
        $candidatesFemale = Candidate::where('gender','female')->get() ;
        return view('candidates.index',
            [
                'candidates'=>$candidates,
                'candidatesFemale'=>$candidatesFemale,
                'candidatesMale'=>$candidatesMale
            ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($event_id = null)
    {
        //
        $event = null;
        if($event_id){
            // $events = Event::where('user_id',Auth::user()->id)->first();
            $event = Event::find($event_id);

            $eventcandidates = Event::find($event->id)
                                    ->candidates()
                                    ->orderBy('number')
                                    ->get();
            
            $availcandidates = Candidate::whereNotIn('id',$eventcandidates->pluck('id'))
                            ->orderBy('lastname')
                            ->get();
        }
        return view('candidates.create',['event_id'=>$event_id,'eventsss'=>$event,'candidates'=>$availcandidates]);
    }

    /**
     * 
     * 
     */

    // -------------------- EDIT CANDIDATE ----------------------- //

    public function editcandidate(Request $request,$candidate_id,$event_id){

        if($request->ajax()){
            $event = Event::find($event_id)->candidates->where('id',$candidate_id)->first();
            return response($event);
        }

        $candidate = Candidate::find($candidate_id);
        $event = Event::find($event_id);
        if(!($candidate && $event)){
            return redirect()->route('events.index')
                            ->with('sucess','Candidate or Event not found');
        }
        $candidate = Candidate::find($candidate_id);
        return view('candidates.edit',['candidates'=>$candidate,'events'=>$event,'event_id'=>$event_id]);
    }

    // -------------------- UPDATE CANDIDATE ----------------------- //

    public function updatecandidate(Request $request, $candidate_id,$event_id){

        
        
        $candidate = Candidate::find($candidate_id);
        $event = Event::find($event_id);

        $candidateEventNumber = Event::find($event->id)
                                        ->candidates()
                                        ->whereGender($candidate->gender)
                                        ->where('number',$request->input('number'))
                                        ->first();
        if($candidateEventNumber){
            if($request->ajax()){
                return response(['warnings'=>'Candidate Number already used']);
            }
            return back()->withInput()->with('warnings','Candidate number not available');
        }
        $event->candidates()->updateExistingPivot($candidate->id,[
            'number'=>$request->input('number')
        ]);

        if($request->ajax()){
                return Response::json(array('success'=>'Candidate Number Updated','number'=>$request->input('number'),'candidate'=>$candidate,'event'=>$event));
        }

        // return redirect()->route('events.show',['events'=>$event])
        //                     ->with('success','Candidate updated');

    }

    /**
     * 
     * ******************* R E M O V E C A N D I D A T E *************
     */

     public function removecandidate($candidate_id,$event_id){
        
        $candidate = Candidate::find($candidate_id);
        $candidatepivot = CandidateEvent::where('candidate_id',$candidate_id)
                                    ->where('event_id',$event_id);
        $event = Event::find($event_id);
        if($candidatepivot->delete()){
            // return redirect()->route('events.show',['events'=>$event])
            //         ->with('success','Candidate removed successfully');
            return Response::json(array('success'=> 'Candidate Removed','candidate'=>$candidate));
        }

        // return redirect()->route('candidates.index')
        //             ->with('errors','Error removing candidate');
        return Response::json(array('errors'=> $validator->getMessageBag()->toarray()));
     }



    /**
     * 
     * 
     */

    public function addcandidate(Request $request,$form = null){

       
        $candidate = Candidate::find($request->input('candidate_id'));
           $event = Event::find($request->input('event_id'));
        

        if($form == 2){ 
            
            $validator = Validator::make($request->all(),[
                'candidate_id'=>'required|integer',
                'number'=>'required|integer'
            ],[
                'candidate_id.required'=>'Select candidate',
                'candidate_id.integer'=>'Candidate not found',
                'number.required'=>'Enter candidate number',
                'number.integer'=>'Invalid candidate number'
            ]);

            

            if($validator->fails()){
                if($request->ajax()){
                    return Response::json(array('errors'=> $validator->getMessageBag()->toarray()));
                }
                // return redirect()->route('events.show',['event'=>$event->id])
                //             ->with('errors',' Candid');
                return back()->withInput()->with('errors','Error Updating');
                            
            }
            

            $candidateEvent = CandidateEvent::where('candidate_id',$candidate->id)
                                        ->where('event_id',$event->id)
                                        ->first();
                                        
            $candidateEventNumber = Event::find($event->id)
                                        ->candidates()
                                        ->whereGender($candidate->gender)
                                        ->where('number',$request->input('number'))
                                        ->first();
                                        
            if(Auth::user()->id == $event->user_id){

                if($candidateEventNumber){
                    if($request->ajax()){
                        return Response::json(array('warnings'=>'Candidate number already used'));
                    }
                     return redirect("candidates/create/$event->id")
                                 ->with('warnings',' Candidate number already used');
                }

                if($event && (!$candidateEvent)){
                    $candidate = Candidate::where('id',$request->input('candidate_id'))->first();
                    $newcan = $event->candidates()->save($candidate,['number'=>$request->input('number')]);
                    
               
                    
                    // return redirect("candidates/create/$event->id")
                    //                     ->with('success','Added new candidate to event');
                    if($request->ajax()){
                        return Response::json(array('success'=>'Added new Candidate','candidate'=>$newcan,'event'=>$event,'number'=>$request->input('number')));
                    }
                       return redirect()->route('events.show',['events'=>$event->id])
                                ->with('success','Added new Candidate to event');
                }
                if($request->ajax()){
                    return Response::json(array('warnings'=>'Candidate already added','candidatevent'=>$candidateEvent));
                }
                return redirect("candidates/create/$event->id")
                                  ->with('warnings',' Candidate already added');
            }
        }


        $validator = Validator::make($request->all(),[
            'firstname'=>'required|regex:/^[\pLa-zA-Z\s]+$/u',
            'middlename'=>'required|regex:/[a-zA-Z\s]+/u',
            'lastname'=>'required|regex:/^[\pLa-zA-Z\s]+$/u',
            'gender'=>'required',
            'number'=>'required|integer'
        ]);

        if($validator->fails()){
            if($request->ajax()){
                return Response::json(array('errors'=> $validator->getMessageBag()->toarray()));
            }
            return redirect('candidates/create')
                    ->withErrors($validator)
                    ->withInput();
        }

        $candidateEventNumber = Event::find($event->id)
                                ->candidates()
                                ->whereGender($request->input('gender'))
                                ->where('number',$request->input('number'))
                                ->first();


        if($candidateEventNumber){
            if($request->ajax()){
                return Response::json(array('warnings'=>'Candidate number already used'));
            }
            // return redirect("candidates/create/$event->id")
            //                 ->with('warnings',' Candidate number already used');
            return back()->withInput()->with('warnings','Candidate number already used');
        }

       
        
        // if($newCandidate){
            if(Auth::user()->id == $event->user_id){
                 $newCandidate = Candidate::create([
                    'gender'=>$request->input('gender'),
                    'firstname'=>$request->input('firstname'),
                    'lastname'=>$request->input('lastname'),
                    'middlename'=>$request->input('middlename')
                ]);

                if($event){
                   $event->candidates()->save($newCandidate,['number'=>$request->input('number')]);

                    // return redirect("candidates/create/$event->id")
                    //                     ->with('success','Added new candidate to event');
                    if($request->ajax()){
                        return Response::json(array('success'=>'Added new Candidate','candidate'=>$newCandidate,'event'=>$event));
                    }
                       return redirect()->route('events.show',['events'=>$event->id])
                                ->with('success','Added new Candidate to event');
                    
                }
            }
        // }
       
        if($request->ajax()){
            return response()->json(['warnings'=>'Event not found']);
        }
        return redirect()->route('events.index')
                        ->with('errors',' Event not found');
        
    }

    public function returnView(Request $request){

        $candidate = Candidate::find($request->candidate_id);

        $event = Event::find($request->event_id)->candidates->where('id',$candidate->id)->first();
        
        return view('events.candidates.ajaxnewcandidate',['candidate'=>$event]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreFormValidation $request)
    {
        //

        $validator = Validator::make($request->all(),[
            'firstname'=>'required|regex:/[a-z\s]+/u',
            'middlename'=>'required|alpha',
            'lastname'=>'required|alpha',
            'gender'=>'required'
        ]);

        if($validator->fails()){
            return redirect('candidates/create')
                    ->withErrors($validator)
                    ->withInput();
        }
        // $validatedData = $request->validate([
        //     'firstname'=>'required|alpha',
        //     'middlename'=>'required|alpha',
        //     'lastname'=>'required|alpha',
        //     'gender'=>'required'
        // ]);

        $newCandidate = Candidate::create([
            'gender'=>$request->input('gender'),
            'firstname'=>$request->input('firstname'),
            'lastname'=>$request->input('lastname'),
            'middlename'=>$request->input('middlename')
        ]);

        if($newCandidate){
            return redirect()->route('candidates.index')
                            ->with('success','Added new Candidate');
        }

        return back()->withInput()->with('errors','Error');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Candidate  $candidate
     * @return \Illuminate\Http\Response
     */
    public function show(Candidate $candidate)
    {
        //
        $candidate = Candidate::find($candidate->id);
        return view('candidates.show',['candidates'=>$candidate]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Candidate  $candidate
     * @return \Illuminate\Http\Response
     */
    public function edit(Candidate $candidate)
    {
        //
        $candidate = Candidate::find($candidate->id);   
        $events = null;     
        return view('candidates.edit',['candidates'=>$candidate,'events'=>null,'event_id'=>null]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Candidate  $candidate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Candidate $candidate)
    {
        //
        $candidateUpdate = Candidate::where('id',$candidate->id)
                                    ->update([
                                        'firstname'=>$request->input('firstname'),
                                        'lastname'=>$request->input('lastname'),
                                        'middlename'=>$request->input('middlename'),
                                        'gender'=>$request->input('gender')
                                    ]);
        if($candidateUpdate){
            return redirect()->route('candidates.index')
                            ->with('success',"$candidate->lastname  has been updated successfully");
        }
        return back()->withInput();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Candidate  $candidate
     * @return \Illuminate\Http\Response
     */
    public function destroy(Candidate $candidate)
    {
        //
        $candidate = Candidate::find($candidate->id);
        if($candidate->delete()){
            return redirect()->route('candidates.index')
                    ->with('success','Candidate removed successfully');
        }

        return redirect()->route('candidates.index')
                    ->with('errors','Error removing candidate');
    }

    public function getCandidate(Request $request){
       
    }
}


