<?php
require_once('ormy.php');
require_once('model.php');
require_once('unity_test.php');

//
// unit tests
//

unity_set_teardown(function(){
	global $database;
	new ormy\Query("delete from users where login = 'bob'", $database);
});
	
$database = new ormy\Database("localhost", "user", "pass", "ormy_test");

$users = new ormy\Mapping(
	$database,
	"users", array("id", "login", "email", "postal_code"),
	"User", array("id", "login", "email", "postal_code"));
	
test("create bob", function (){
	$bob = new User();
	$bob->login = "bob";
	$bob->password = "secret";
	$bob->email = "bob@bob.com";
	$bob->postal_code = "90210";

	global $users;
	
	$id = $users->create($bob);
	
	assert_true($id != null);
	$bob_loaded = $users->load($id);
	assert_equals($bob_loaded->login, "bob");
	
	$users->delete($id);
	$bob_loaded = $users->load($id);
	assert_true(!$bob_loaded);
});

test("update bob", function(){
	$bob = new User();
	$bob->login = "bob";
	$bob->password = "secret";
	$bob->email = "bob@bob.com";
	$bob->postal_code = "90210";

	global $users;
	
	$id = $users->create($bob);
	
	assert_true($id != null);
	$bob_loaded = $users->load($id);
	assert_equals($bob_loaded->email, "bob@bob.com");
	$bob_loaded->email = "bob@gmail.com";
	$users->update($bob_loaded);
	
	$bob_loaded = $users->load($id);
	assert_equals($bob_loaded->email, "bob@gmail.com");
	
	$users->delete($id);
	$bob_loaded = $users->load($id);
	assert_true(!$bob_loaded);
});

test("load where", function(){
	$bob = new User();
	$bob->login = "bob";
	$bob->password = "secret";
	$bob->email = "bob@bob.com";
	$bob->postal_code = "90210";

	global $users;
	
	$id = $users->create($bob);
	
	assert_true($id != null);
	$bob_loaded = $users->load_where("email = 'bob@bob.com'");
	assert_true($bob_loaded && $bob_loaded[0]);
	assert_equals($bob_loaded[0]->login, "bob");
	
	$users->delete($id);
	$bob_loaded = $users->load($id);
	assert_true(!$bob_loaded);
});

unity_summary();

?>