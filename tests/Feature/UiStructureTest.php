<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dashboard navigation is directly available without a disclosure control', function () {
    $this->actingAs(User::factory()->administrator()->create())->get(route('administrator.dashboard'))->assertOk()
        ->assertSee('href="#main-content"', false)->assertSee('id="main-content"', false)
        ->assertSee('images/sipendas-logo.png', false)
        ->assertSee('aria-label="Navigasi utama"', false)->assertSee('Informasi pembelajaran')
        ->assertDontSee('Menu navigasi');
});

test('learning navigation marks the current page', function () {
    $this->actingAs(User::factory()->create())->get(route('student.materials.index'))->assertOk()
        ->assertSee('aria-current="page"', false)->assertSee('Belum ada materi');
});

test('account form includes inline validation and password visibility controls', function () {
    $response = $this->actingAs(User::factory()->administrator()->create())
        ->get(route('administrator.teachers.create'));

    $response->assertOk()
        ->assertSee('data-validated-form', false)
        ->assertSee('novalidate', false)
        ->assertSee('aria-controls="password"', false)
        ->assertSee('aria-controls="password_confirmation"', false)
        ->assertSee('data-digits-only', false)
        ->assertSee('inputmode="numeric"', false)
        ->assertDontSee('Role akun existing tetap');
});

test('success status and destructive forms expose sweet alert hooks', function () {
    $admin = User::factory()->administrator()->create();

    $this->actingAs($admin)->withSession(['status' => 'Data berhasil disimpan.'])
        ->get(route('administrator.dashboard'))->assertOk()
        ->assertSee('data-swal-success', false)
        ->assertSee('Data berhasil disimpan.');

    $this->get(route('administrator.users.index'))->assertOk()
        ->assertSee('data-confirm=', false)
        ->assertSee('Nonaktifkan');
});
