<?php

namespace App\Http\Controllers;

use App\Helper\Helper;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\Ses\SesClient;


class UserCognitoController extends Controller
{
    public function index()
    {

        $client = new CognitoIdentityProviderClient([
            'version' => '2016-04-18',
            'region' => 'us-east-2',
            'credentials' => [
                            'key'    => env('AWS_COGNITO_USER_KEY'),
                            'secret' => env('AWS_COGNITO_USER_SECRET'),
                        ],
        ]);

        $result = $client->listUsers([
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'), // REQUIRED
        ]);

        $userList = $result->get('Users');
        $array = [];
        foreach($userList as $user) {

                $date = strtotime($user["UserCreateDate"]);
                if (date('m/d/Y h:i:s', $date) > date('m/d/Y h:i:s', strtotime("-30 days"))) {
                    $patientRecord = patient::where('sub',$user["Username"])->get();
                    //return $patientRecord[0]["is_complete"];
                    //if ($patientRecord[0]["sendInformation"]) {
                    //    return $user;
                    //}

                    foreach($user["Attributes"] as $attribute){
                        if ($attribute["Name"] == 'email'){
                            $array[] =  $attribute["Value"];
                        }
                    }
                }

                
        }

     //return $array;
     return $userList;

        $SesClient = new SesClient([
	            'version' => '2010-12-01',
	            'region' => 'us-east-2',
	            'credentials' => [
                    'key'    => env('AWS_COGNITO_USER_KEY'),
                    'secret' => env('AWS_COGNITO_USER_SECRET'),
	            ],
        ]);
        //$template_name = 'update-profile';
        $template_name = 'new-trials';
        $sender_email = 'info@cheryl.app';
        $recipient_emails = ['mattk@abunchofcreators.com','mking@abunchofcreators.com'];

        //try {
            $result = $SesClient->sendTemplatedEmail([
                'Destination' => [
                    'ToAddresses' => $recipient_emails,
                ],
                'ReplyToAddresses' => [$sender_email],
                'Source' => $sender_email,
        
                'Template' => $template_name,
                'TemplateData' => '{ }'
            ]);
            return($result);
       // } catch (AwsException $e) {
            // output error message if fails
       //     return $e->getMessage();
            
       // }
    }

}