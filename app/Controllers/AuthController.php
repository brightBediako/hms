<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;

final class AuthController
{
    public function showLogin(Request $request): void
    {
        View::render('auth/login', [
            'title' => 'Sign in',
            'error' => Session::pullFlash('error'),
            'errors' => Session::pullFlash('errors') ?? [],
            'email' => Session::pullFlash('old_email') ?? '',
        ], 'auth');
    }

    public function login(Request $request): void
    {
        $validator = new Validator();
        $data = $validator->validate($request->post(), [
            'email' => 'required|email|max:150',
            'password' => 'required|min:6|max:255',
        ]);

        if ($data === null) {
            Session::flash('errors', $validator->firstErrors());
            Session::flash('old_email', (string) $request->input('email', ''));
            redirect('/login');
        }

        if (!Auth::attempt((string) $data['email'], (string) $data['password'])) {
            Session::flash('error', 'Invalid email or password.');
            Session::flash('old_email', (string) $data['email']);
            redirect('/login');
        }

        Session::flash('success', 'Welcome back.');
        redirect(Auth::homePath());
    }

    public function logout(Request $request): void
    {
        Auth::logout();
        Session::start();
        Session::flash('success', 'You have been signed out.');
        redirect('/login');
    }
}
