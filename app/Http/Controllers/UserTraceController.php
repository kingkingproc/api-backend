<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserTraceController extends Controller
{
    public function store(Request $request)
    {
        //
        $the_object = Helper::verifyJasonToken($request);
        //return $the_object->sub;
        //return $request;
        DB::table('user_trace')->insert([
            'user_trace_sub' => $the_object->sub,
            'user_trace_element' => $request['trace_element'],
            'user_trace_string' => $request['trace_string']
        ]);
    }

}
