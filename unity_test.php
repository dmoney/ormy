<?php

//
// a unit testing framework
//

$unity_fails = 0;
$unity_tear_down = null;

class AssertionError extends Exception {}

function assert_true($proposition, $message = "ASSERTION FAILED", $backtrace_offset = 0){
	global $unity_fails;
	$backtrace = debug_backtrace();
	//print_r($backtrace);
	$line = $backtrace[$backtrace_offset]['line'];
	$file = $backtrace[$backtrace_offset]['file'];
	
	if (!$proposition) {
		$unity_fails++;
		throw new AssertionError("$file:$line: $message");
	}
}

function assert_equals($val1, $val2){
	assert_true($val1 == $val2, "ASSERT_EQUALS: $val1 != $val2", 1);
}

function test($name, $fn){
	try {
		$fn();
	}
	catch (AssertionError $e){
		$m = $e->getMessage();
		echo("<p>failed \"$name\": <br/> $m</p>");
	}
	
	global $unity_tear_down;
	if ($unity_tear_down) {
		$unity_tear_down();
	}
}

function unity_summary(){
	global $unity_fails;
	echo $unity_fails ? "You fail $unity_fails times!" : "Stuff passed!";
}

function unity_set_teardown($fn){
	global $unity_teardown;
	$unity_teardown = $fn;
}
?>