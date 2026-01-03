<?php

namespace cartographica\tests;

abstract class TestCase
{
  private array $results = [];

  protected function post($url,array $data)
  {
    $body = http_build_query($data);
    $options = [
      "http"=> [
        "method"=>"POST",
        "header"=>
          "Content-Type: application/x-www-form-urlencoded\r\n".
          "Content-Length: ".strlen($body)."\r\n".
          "User-Agent: CartographicaTestHarness\r\n".
          "Connection: close\r\n",
        "content"=>$body,
        "ignore_errors"=>true
      ]
    ];
    return file_get_contents($url."&test",false,stream_context_create($options));
  }

  protected function basic_test(string $function_name, bool $ok, string $message = '', $context = []): void
  {
    if (empty($message))
    {
      $message=$function_name." failed";
    }
    $this->results[]=[
      "ok"=>$ok,
      "message"=>$message,
      "context"=>$context
    ];
    if (!$ok)
    {
      echo "\n--- DEBUG ---\n";
      var_dump($context);
      echo "\n-------------\n";
    }
  }

  protected function assertTrue($condition, string $message="", $context=[]): void
  {
    $ok=(bool)$condition;
    $this->basic_test("assertTrue",$ok,$message,$context ?: ["value"=>$condition]);
  }

  protected function assertFalse($condition, string $message="", $context=[]): void
  {
    $ok=!((bool)$condition);
    $this->basic_test("assertFalse",$ok,$message,$context ?: ["value"=>$condition]);
  }

  protected function assertEquals($expected, $actual, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Expected ".var_export($expected,true)." but got ".var_export($actual,true);
    }
    $ok=($expected==$actual);
    $this->basic_test("assertEquals",$ok,$message,$context ?: ["expected"=>$expected, "actual"=>$actual]);
  }

  public function assertNotEquals($expected, $actual, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Did not expect ".var_export($expected,true);
    }
    $ok=($expected<>$actual);
    $this->basic_test("assertNotEquals",$ok,$message,$context ?: ["expected"=>$expected, "actual"=>$actual]);
  }

  protected function assertStrictEquals($expected, $actual, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Expected ".var_export($expected,true)." but got ".var_export($actual,true);
    }
    $ok=($expected===$actual);
    $this->basic_test("assertStrictEquals",$ok,$message,$context ?: ["expected"=>$expected, "actual"=>$actual]);
  }

  public function assertStrictNotEquals($expected, $actual, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Did not expect ".var_export($expected,true);
    }
    $ok=($expected!==$actual);
    $this->basic_test("assertStrictNotEquals",$ok,$message,$context ?: ["expected"=>$expected, "actual"=>$actual]);
  }

  public function assertArrayHasKey(string $key, $array, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Array does not contain expected key '$key'";
    }
    $ok=array_key_exists($key,$array ?? []);
    $this->basic_test("assertArrayHasKey",$ok,$message,$context ?: $array);
  }

  public function assertArrayNotHasKey(string $key, $array, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Array should not contain key '$key'";
    }
    $ok=!array_key_exists($key,$array ?? []);
    $this->basic_test("assertArrayNotHasKey",$ok,$message,$context ?: $array);
  }

  public function assertIsArray($array, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Value is not an array";
    }
    $ok=is_array($array);
    $this->basic_test("assertIsArray",$ok,$message,$context ?: ["value"=>$array]);
  }

  public function assertArrayCount(int $expected, array $array, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Expected array length $expected but got ".count($array);
    }
    $ok=count($array)===$expected;
    $this->basic_test("assertArrayCount",$ok,$message,$context ?: $array);
  }

  public function assertInArray($needle, array $haystack, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Expected value to be in array";
    }
    $ok=in_array($needle,$haystack,true);
    $this->basic_test("assertInArray",$ok,$message,$context ?: ["needle"=>$needle, "haystack"=>$haystack]);
  }

  public function assertLessThan($value, $limit, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Expected < $limit";
    }
    $ok=$value<$limit;
    $this->basic_test("assertLessThan",$ok,$message,$context ?: ["value"=>$value, "limit"=>$limit]);
  }

  public function assertGreaterThan($value, $limit, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Expected > $limit";
    }
    $ok=$value>$limit;
    $this->basic_test("assertGreaterThan",$ok,$message,$context ?: ["value"=>$value, "limit"=>$limit]);
  }

  public function assertNotEmpty($actual, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Value is empty";
    }
    $ok=!empty($actual);
    $this->basic_test("assertNotEmpty",$ok,$message,$context ?: ["value"=>$actual]);
  }

  public function assertIsEmpty($actual, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Value is not empty";
    }
    $ok=empty($actual);
    $this->basic_test("assertIsEmpty",$ok,$message,$context ?: ["value"=>$actual]);
  }

  public function assertNotNull($actual, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Value is null";
    }
    $ok=!is_null($actual);
    $this->basic_test("assertNotNull",$ok,$message,$context ?: ["value"=>$actual]);
  }

  public function assertNull($actual, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Value is not null";
    }
    $ok=is_null($actual);
    $this->basic_test("assertNull",$ok,$message,$context ?: ["value"=>$actual]);
  }

  public function assertJson($raw, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Invalid JSON";
    }
    $decoded=json_decode($raw,true);
    $ok=!is_null($decoded);
    $this->basic_test("assertJson",$ok,$message,$context ?: ["raw"=>$raw]);
    return $decoded;
  }

  public function assertStatusOK(array $response, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Expected ok:true";
    }
    $ok=isset($response["ok"]) && $response["ok"]===true;
    $this->basic_test("assertStatusOK",$ok,$message,$context ?: $response);
  }

  public function assertStatusError(array $response, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Expected ok:false";
    }
    $ok=isset($response["ok"]) && $response["ok"]===false;
    $this->basic_test("assertStatusError",$ok,$message,$context ?: $response);
  }

  public function assertErrorMessage(array $response, string $expected, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Expected error message '$expected'";
    }
    $ok=isset($response["error"]) && $response["error"]===$expected;
    $this->basic_test("assertErrorMessage",$ok,$message,$context ?: $response);
  }

  public function assertContains(string $needle, string $haystack, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Expected string to contain '$needle'";
    }
    $ok=str_contains($haystack,$needle);
    $this->basic_test("assertContains",$ok,$message,$context ?: ["needle"=>$needle, "haystack"=>$haystack]);
  }

  public function assertInstanceOf(string $class, $object, string $message="", $context=[]): void
  {
    if (empty($message))
    {
      $message="Expected instance of $class";
    }
    $ok=$object instanceof $class;
    $default_context=["object"=>$object,"expected_class"=>$class,"actual_class"=>is_object($object) ? get_class($object) : gettype($object)];
    $this->basic_test("assertInstanceOf",$ok,$message,$context ?: $default_context);
  }

  protected function beforeEach(): void
  {
  }
  
  protected function afterEach(): void
  {
  }
  
  public function run(): array
  {
    $this->results=[];
    $this->beforeEach();
    $this->test();
    $this->afterEach();
    return $this->results;
  }

  protected function test(): void
  {
  }
}
