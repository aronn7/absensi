<?php
namespace Tests\Feature;
use Tests\TestCase;
class ExampleTest extends TestCase {public function test_unauthenticated_users_are_redirected(): void {$this->get('/admin/students')->assertRedirect('/');}}
