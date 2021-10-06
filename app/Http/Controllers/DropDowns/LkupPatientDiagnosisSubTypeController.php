<?php

namespace App\Http\Controllers\DropDowns;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LkupPatientDiagnosisCancerSubType;

class LkupPatientDiagnosisSubTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return lkuppatientdiagnosiscancersubtype::select('cancer_sub_type_id AS key','cancer_sub_type_label AS value','cancer_type_id AS type_key')->get();
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

    public function search($label)
    {
        return lkuppatientdiagnosiscancersubtype::select('cancer_sub_type_id AS key','cancer_sub_type_label AS value')
                                                ->where('cancer_type_id', '=', $label)
                                                ->get();
    }
}
