<?php

namespace App\Http\Controllers;

use App\Models\LkupPatientDiagnosisRemoteSite;
use Illuminate\Http\Request;

class LkupPatientDiagnosisRemoteSiteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return lkuppatientdiagnosisremotesite::select('remote_site_id AS key','remote_site_label AS value')->get();
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

     /**
     * Search for the specified resource in storage.
     *
     * @param  str  $label
     * @return \Illuminate\Http\Response
     */
    public function search($label)
    {
        return lkuppatientdiagnosisremotesite::select('remote_site_id AS key','remote_site_label AS value')
                                                ->where('remote_site_label', 'ilike', $label.'%')
                                                ->get();
    }
}
