<?php

namespace Tests\Feature;

use App\Rules\RegistrationRule;
use App\Rules\Uppercase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as ValidationValidator;
use Tests\TestCase;

class ValidatorTest extends TestCase
{
    public function testValidator()
    {
        $data = [
            "username" => "admin",
            "password" =>"12345"
        ];

        $rules = [
            "username" => "required",
            "password" => "required"
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);

        self::assertTrue($validator->passes());
        self::assertFalse($validator->fails());
    }

    public function testValidatorInvalid()
    {
        $data = [
            "username" => "",
            "password" =>""
        ];

        $rules = [
            "username" => "required",
            "password" => "required"
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);

        self::assertFalse($validator->passes());
        self::assertTrue($validator->fails());

        $message = $validator->getMessageBag();

        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorException()
    {
        $data = [
            "username" => "",
            "password" =>""
        ];

        $rules = [
            "username" => "required",
            "password" => "required"
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);

        try {
            $validator->validate();
            self::fail("ValidationException not thrown");
        }catch (ValidationException $exception){
            self::assertNotNull($exception->validator);
            $message = $exception->validator->errors();
            Log::error($message->toJson(JSON_PRETTY_PRINT));
        }
    }

    public function testValidatorMultipleRules()
    {
        App::setLocale("id");

        $data = [
            "username" => "bram",
            "password" => "bram"
        ];

        $rules = [
            "username" => "required|email|max:100",
            "password" => ["required","min:6", "max:20"]
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);

        self::assertFalse($validator->passes());
        self::assertTrue($validator->fails());

        $message = $validator->getMessageBag();

        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorValidata()
    {
        $data = [
            "username" => "admin@bram.com",
            "password" => "rahasia",
            "admin" => true,
            "others" => "007x"
        ];

        $rules = [
            "username" => "required|email|max:100",
            "password" => "required|min:6|max:20"
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);

        try {
            $valid = $validator->validate();
            Log::info(json_encode($valid, JSON_PRETTY_PRINT));
        }catch (ValidationException $exception){
            self::assertNotNull($exception->validator);
            $message = $exception->validator->errors();
            Log::error($message->toJson(JSON_PRETTY_PRINT));
        }
    }

    public function testValidatorInlineMessage()
    {       
        $data = [
            "username" => "bram",
            "password" => "bram"
        ];

        $rules = [
            "username" => "required|email|max:100",
            "password" => ["required","min:6", "max:20"]
        ];

        $messages = [
            "required" => ":attribute harus diisi",
            "email" => ":attribute harus berupa email",
            "min" => ":attribute minimal :min karakter",
            "max" => ":attribute maksimal :max karakter",
        ];

        $validator = Validator::make($data, $rules, $messages);
        self::assertNotNull($validator);

        self::assertFalse($validator->passes());
        self::assertTrue($validator->fails());

        $message = $validator->getMessageBag();

        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorAdditionalValidation()
    {
        $data = [
            "username" => "bram@007x.com",
            "password" => "bram@007x.com"
        ];

        $rules = [
            "username" => "required|email|max:100",
            "password" => ["required","min:6", "max:20"]
        ];

        $validator = Validator::make($data, $rules);
        $validator->after(function (ValidationValidator $validator){
            $data = $validator->getData();
            if($data['username'] == $data['password']){
                $validator->errors()->add("password", "Password tidak boleh sama dengan username");
            }
        });
        self::assertNotNull($validator);

        self::assertFalse($validator->passes());
        self::assertTrue($validator->fails());

        $message = $validator->getMessageBag();

        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorCustomRule()
    {
        $data = [
            "username" => "bram@007x.com",
            "password" => "bram@007x.com"
        ];

        $rules = [
            "username" => ["required", "email", "max:100", new Uppercase()],
            "password" => ["required","min:6", "max:20", new RegistrationRule()]
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);

        self::assertFalse($validator->passes());
        self::assertTrue($validator->fails());

        $message = $validator->getMessageBag();

        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorCustomFunctionRule()
    {
        $data = [
            "username" => "bram@007x.com",
            "password" => "bram@007x.com"
        ];

        $rules = [
            "username" => ["required", "email", "max:100", function(string $attribute, string $value, \Closure $fail){
                if(strtoupper($value) != $value){
                    $fail("The field $attribute must be UPPERCASE");
                }
            }],
            "password" => ["required","min:6", "max:20", new RegistrationRule()]
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);

        self::assertFalse($validator->passes());
        self::assertTrue($validator->fails());

        $message = $validator->getMessageBag();

        Log::info($message->toJson(JSON_PRETTY_PRINT));
    }

    public function testValidatorRuleClasses()
    {
        $data = [
            "username" => "Bram",
            "password" => "bram@007x.com"
        ];

        $rules = [
            "username" => ["required", new In(["Bram", "Domes", "Kirana"])],
            "password" => ["required", Password::min(6)->letters()->numbers()->symbols()]
        ];

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);

        $validator = Validator::make($data, $rules);
        self::assertNotNull($validator);

        self::assertTrue($validator->passes());
    }

    public function testNestedArrayValidation()
    {
        $data = [
            "name" => [
                "first" => "Bramsata",
                "last" => "Albatio"
            ],
            "address" => [
                "street" => "Kamulan",
                "city" => "Trenggalek",
                "country" => "Indonesia"
            ]
        ];

        $rules = [
            "name.first" => ["required", "max:100"],
            "name.last" => ["max:100"],
            "address.street" => ["max:200"],
            "address.city" => ["required", "max:100"],
            "address.country" => ["required", "max:100"],
        ];

        $validator = Validator::make($data, $rules);
        self::assertTrue($validator->passes());
    }

    public function testNestedIndexedArrayValidation()
    {
        $data = [
            "name" => [
                "first" => "Bramsata",
                "last" => "Albatio"
            ],
            "address" => [
                [
                    "street" => "Kamulan",
                    "city" => "Trenggalek",
                    "country" => "Indonesia"
                ],
                [
                    "street" => "Ngembel",
                    "city" => "Trenggalek",
                    "country" => "Indonesia"
                ]
            ]
        ];

        $rules = [
            "name.first" => ["required", "max:100"],
            "name.last" => ["max:100"],
            "address.*.street" => ["max:200"],
            "address.*.city" => ["required", "max:100"],
            "address.*.country" => ["required", "max:100"],
        ];

        $validator = Validator::make($data, $rules);
        self::assertTrue($validator->passes());
    }
}
