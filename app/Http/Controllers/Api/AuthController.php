<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Openaidata;
use Illuminate\Http\Request;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{

    //your OpenAI API key
    private $apiKey = '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';

    // create user through api
    public function createUser(Request $request)
    {
        try {
            //Validated
            $validateUser = Validator::make(
                $request->all(),
                [
                    'name' => 'required',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'required'
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'status' => true,
                'message' => 'User Created Successfully',
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    // login through api
    public function loginUser(Request $request)
    {
        try {
            $validateUser = Validator::make(
                $request->all(),
                [
                    'email' => 'required|email',
                    'password' => 'required'
                ]
            );

            if ($validateUser->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'validation error',
                    'errors' => $validateUser->errors()
                ], 401);
            }

            if (!Auth::attempt($request->only(['email', 'password']))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not match with our record.',
                ], 401);
            }

            $user = User::where('email', $request->email)->first();

            return response()->json([
                'status' => true,
                'message' => 'User Logged In Successfully',
                'token' => $user->createToken("API TOKEN")->plainTextToken
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage()
            ], 500);
        }
    }

    //handle request
    public function askQuestion(Request $request)
    {
        // check if user authenticate or not
        if (!Auth::guard('sanctum')->check()) {
            return response()->json(['message' => 'User not authenticated. Please login first.'], 401);
        }

        // Get the authenticated user's ID
        $usermail = Auth::guard('sanctum')->user()->email;

        // question input data
        $question = $request->input('question');

        // Check if a question was provided
        if (empty($question)) {
            return response()->json(['error' => 'Please provide a question.'], 400);
        }

        // Make a request to the OpenAI API
        $response = $this->makeOpenAIRequest($question);

        // Check if the request was successful
        if ($response->getStatusCode() === 200) {
            $result = json_decode($response->getBody()->getContents(), true);

            // Extract and return the generated text
            $generatedText = $result['choices'][0]['message']['content'];
            // after get ai data insert into database
            $Openaidata = Openaidata::create([
                'user_mail' => $usermail,
                'question' => $question,
                'result' => $generatedText
            ]);
            return response()->json(['result' => $generatedText]);
        } else {
            return response()->json(['error' => 'Error communicating with the OpenAI API.'], 500);
        }
    }

    //process OpenAI API call 
    private function makeOpenAIRequest($question)
    {
        $client = new Client();
        $endpoint = 'https://api.openai.com/v1/chat/completions'; // Updated endpoint for chat completions

        // Set additional parameters as needed
        $params = [
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => $question],
            ],
            'max_tokens' => 150,
            'model' => 'gpt-3.5-turbo',
            'temperature' => 0.7
        ];

        // Set headers with the API key
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        // Make the API request
        return $client->post($endpoint, [
            'json' => $params,
            'headers' => $headers,
        ]);
    }

    //fetch OpenAI data by user email
    public function getAidata($email)
    {
        // check if user authenticate or not
        if (!Auth::guard('sanctum')->check()) {
            return response()->json(['message' => 'User not authenticated. Please login first.'], 401);
        }

        $data = Openaidata::where('user_mail', $email)->get();

        if ($data) {
            return response()->json($data);
        } else {
            return response()->json(['message' => 'Data not found'], 404);
        }
    }
}
