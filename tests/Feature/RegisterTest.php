<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    // 名前が未入力の場合バリデーションメッセージが表示されるテスト
    public function test_name_is_required(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // nameフィールドが空の場合のエラーメッセージを検証
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    // メールアドレスが未入力の場合バリデーションメッセージが表示されるテスト
    public function test_email_is_required(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // emailフィールドが空の場合のエラーメッセージを検証
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    // パスワードが8文字未満の場合バリデーションメッセージが表示されるテスト
    public function test_password_must_be_at_least_8_characters(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]);

        // passwordフィールドが8文字未満の場合のエラーメッセージを検証
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    // パスワードが一致しない場合バリデーションメッセージが表示されるテスト
    public function test_password_confirmation_does_not_match(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password123',
        ]);

        // passwordとpassword_confirmationが一致しない場合のエラーメッセージを検証
        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません',
        ]);
    }

    // パスワードが未入力の場合バリデーションメッセージが表示されるテスト
    public function test_password_is_required(): void
    {
        $response = $this->post('/register', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => 'password',
        ]);

        // passwordフィールドが空の場合のエラーメッセージを検証
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    // 正しいユーザー情報を入力した場合、ユーザーが正常に登録されるテスト
    public function test_user_can_register_with_valid_data(): void
    {
        $userData = [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];

        $response = $this->post('/register', $userData);

        // 会員登録後のリダイレクトを検証
        $response->assertRedirect();

        // usersテーブルにユーザー情報が保存されていることを検証
        $this->assertDatabaseHas('users', [
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
        ]);
    }
}
