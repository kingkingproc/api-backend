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
            //'PaginationToken' => 'CAISqQIIARKCAggDEv0BAEXxeIBjEWcEPDGfxiT4zVfrjVVe7MPfs2n6v329BsP6eyJAbiI6IlBhZ2luYXRpb25Db250aW51YXRpb25EVE8iLCJuZXh0S2V5IjoiQUFBQUFBQUFDRjkrQVFFQlNKZTQ3WHpGUm15TDYyT0VEZWUybGRxa25XRzJZVTFVcldxejM2aEE0V1JsYm1ZN05EZGtOemhsTldJdE16RTJOeTAwWmpKa0xXSTFZbVF0T1dOaU4yWmxOR1V6TW1JMU93PT0iLCJwYWdpbmF0aW9uRGVwdGgiOjYwLCJwcmV2aW91c1JlcXVlc3RUaW1lIjoxNjgyNTE4OTY3NTI1fRogMslgcrM63ZkBUnvfRhf3+4sddKR61HcI5Dfl1QQ7e2Y=',
        ]);

        //return $result;
        $userList = $result->get('Users');
        $token = $result->get('PaginationToken');
        //return $token;
        $array = [];
        foreach($userList as $user) {
            foreach($user["Attributes"] as $attribute){
                if ($attribute["Name"] == 'email'){
                    $tempEmail =  $attribute["Value"];
                }
                if ($attribute["Name"] == 'sub'){
                    $tempSub =  $attribute["Value"];
                }                
            }
            $testResults = DB::connection('pgsql')->select(" 
            INSERT INTO cognitouser(email, sub)	
            VALUES ('" . $tempEmail . "', '" . $tempSub . "')
            ");
            
            $array[] = $user;
        }
        
        
        for ($x = 0; $x <= 5; $x++) {
            if (!empty($token)) {
                $result = $client->listUsers([
                    'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID'), // REQUIRED
                    'PaginationToken' => $token,
                ]);
                //return $result;
                $userList = $result->get('Users');
                foreach($userList as $user) {
                    foreach($user["Attributes"] as $attribute){
                        if ($attribute["Name"] == 'email'){
                            $tempEmail =  $attribute["Value"];
                        }
                        if ($attribute["Name"] == 'sub'){
                            $tempSub =  $attribute["Value"];
                        }                
                    }
                    $testResults = DB::connection('pgsql')->select(" 
                    INSERT INTO cognitouser (email, sub)	
                    VALUES ('" . $tempEmail . "', '" . $tempSub . "')
                    ");
                    $array[] = $user;
                }
                $token = $result->get('PaginationToken');
            }
          }
        return $array;
        
        //return $userList;
        foreach($userList as $user) {

               // $date = strtotime($user["UserCreateDate"]);
               // if (date('m/d/Y h:i:s', $date) > date('m/d/Y h:i:s', strtotime("-30 days"))) {
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
                //}

                
        }
        $array[] = $token;
     return $array;
     //return $userList;

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