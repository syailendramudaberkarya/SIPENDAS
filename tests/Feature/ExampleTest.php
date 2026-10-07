<?php

test('the application redirects to the login page', function () {
    $this->get('/')->assertRedirect(route('login'));
});
