<?php

test('the signin page returns a successful response', function () {
    $response = $this->get('/signin');

    $response->assertStatus(200);
});
