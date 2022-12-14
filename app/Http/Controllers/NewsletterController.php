<?php

namespace App\Http\Controllers;

use App\Helper\Helper;

use Illuminate\Http\Request;
use App\Models\newsletter;

use Illuminate\Support\Facades\DB;

class NewsletterController extends Controller
{
    // get route 
    public function index()
    {
        $request = request();
        $newsletterRecord = newsletter::where('email', $request['email'])->get();
        
        return $navigationRecord;
    }


    //post route
    public function store(Request $request)
    {

        newsletter::create($request->all());
        return json_encode(array('status' => 'success'));
        
    }


    // put route
    public function update(Request $request)
    {

        $request = request();
        $newsletterRecord = newsletter::where('email', $request['email'])->get();
                
        if (empty($newsletterRecord[0]['email'])) {

            return newsletter::create($request->all());
        } 
        else {

 
            $affected = DB::table('newsletter')
                ->where('email', $request["email"])
                ->update(array(
                    'name_first' => $request["name_first"],
                    'name_last' => $request["name_last"],
                    'email' => $request["email"],
                    'bln_marketing' => $request["bln_marketing"],
                    'message' => $request["message"],
                    'role' => $request["role"],
                    'site' => $request["site"],
                    'source' => $request["source"]
                ));
                $newsletterRecord = newsletter::where('email', $request['email'])->get();
            return $newsletterRecord;    
       
        }
       

    }

}