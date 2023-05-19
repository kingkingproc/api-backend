<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\SurveyStepOne;
use Illuminate\Http\Request;

class SurveyStepOneController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $token = request();
        $the_object = Helper::verifyJasonToken($token);

        $request = $request->all();
        $request['patient']['sub'] = $the_object->sub;
        $request['patient']['email'] = $the_object->email;
        //return $request['patient'];
        return json_encode(['patient'=>surveystepone::create($request['patient'])]);
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
