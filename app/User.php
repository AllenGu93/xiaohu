<?php
//dd(Request::get('password'));
//dd(Request::has('age'));
//dd(Request::all());
namespace App;

use Illuminate\Database\Eloquent\Model;
use Request;
use Hash;

class User extends Model
{
  /*注册api*/
  public function signup()
  {
    $has_username_and_password = $this->has_username_and_password();
    if (!$has_username_and_password)
      return err('用户名和密码皆不可为空');

    $username = $has_username_and_password[0];
    $password = $has_username_and_password[1];

    /*检查用户名是否存在*/
    $user_exists = $this
      ->where('username', $username)
      ->exists();

    if ($user_exists)
      return err('用户名已存在');

    /*加密密码*/
    $hashed_password = bcrypt($password);

    /*存入数据库*/
    $user = $this;
    $user->password = $hashed_password;
    $user->username = $username;
    if ($user->save())
      return suc(['id' => $user->id]);
    else
      return err('db insert failed');
  }

  /*获取用户信息api*/
  public function read()
  {
    if (!rq('id'))
      return err('required id');

    if (rq('id') === 'self') {
      if (!$this->is_logged_in())
        return err('login required');
      $id = session('user_id');
    } else
      $id = rq('id');

    $get = ['id', 'username', 'avatar_url', 'intro'];
    $user = $this->find($id, $get);
    $data = $user->toArray();
    $answer_count = answer_ins()->where('user_id', $id)->count();
    $question_count = question_ins()->where('user_id', $id)->count();
    $data['answer_count'] = $answer_count;
    $data['question_count'] = $question_count;

    return suc($data);
  }

  /*登录api*/
  public function login()
  {
    /*检查用户名和密码是否存在*/
    $has_username_and_password = $this->has_username_and_password();
    if (!$has_username_and_password)
      return err('username and password are required');
    $username = $has_username_and_password[0];
    $password = $has_username_and_password[1];

    /*检查用户是否存在*/
    $user = $this->where('username', $username)->first();
    if (!$user)
      return err('user not exists');

    /*检查密码是否正确*/
    $hashed_password = $user->password;
    if (!Hash::check($password, $hashed_password))
      return err('invalid password');

    /*将用户信息写入Session*/
    session()->put('username', $user->username);
    session()->put('user_id', $user->id);

    return suc(['id' => $user->id]);
  }

  public function has_username_and_password()
  {
    $username = rq('username');
    $password = rq('password');
    /*检查用户名和密码是否为空*/
    if ($username && $password)
      return [$username, $password];
    return false;
  }

  /*登出api*/
  public function logout()
  {
    /*删除username*/
    session()->forget('username');
    /*删除user_id*/
    session()->forget('user_id');
    return suc();
    //return redirect('/');
  }

  /*检测用户是否登录*/
  public function is_logged_in()
  {
    /*如果session中存在user_id就返回user_id, 否则返回false*/
    return is_logged_in();
  }

  /*修改密码api*/
  public function change_password()
  {
    if (!$this->is_logged_in())
      return err('login required');

    if (!rq('old_password') || !rq('new_password'))
      return err('old_password and new_password are required');

    $user = $this->find(session('user_id'));

    if (!Hash::check(rq('old_password'), $user->password))
      return err('invalid old_password');

    $user->password = bcrypt(rq('new_password'));
    return $user->save() ?
      suc() :
      err('db update failed');
  }

  /*找回密码api*/
  public function reset_password()
  {
    if ($this->is_robot())
      return err('max frequency reached');

    if (!rq('phone'))
      return err('phone is required');

    $user = $this->where('phone', rq('phone'))->first();

    if (!$user)
      return err('invalid phone number');

    /*生成验证码*/
    $captcha = $this->generate_captcha();

    $user->phone_captcha = $captcha;

    if ($user->save()) {
      /*如果验证码保存成功, 发送验证码短信*/
      $this->send_sms();

      /*为下一次机器人调用检查做准备*/
      $this->update_robot_time();
      return suc();
    }
    return err('db update failed');
  }

  /*验证找回密码api*/
  public function validate_reset_password()
  {
    if ($this->is_robot(2))
      return err('max frequency reached');

    if (!rq('phone') || !rq('phone_captcha') || !rq('new_password'))
      return err('phone, new_password and phone_captcha are required');

    /*检查用户是否存在*/
    $user = $this->where([
      'phone'         => rq('phone'),
      'phone_captcha' => rq('phone_captcha'),
    ])->first();

    if (!$user)
      return err('invalid phone or invalid phone_captcha');

    /*加密新密码*/
    $user->password = bcrypt(rq('new_password'));
    $this->update_robot_time();
    return $user->save() ?
      suc() : err('db update failed');
  }

  /*检查机器人*/
  public function is_robot($time = 10)
  {
    /*如果session中没有last_action_time说明接口从未被调用过*/
    if (!session('last_action_time'))
      return false;

    $current_time = time();
    $last_active_time = session('last_action_time');

    $elapsed = $current_time - $last_active_time;
    return !($elapsed > $time);
  }

  /*更新机器人行为时间*/
  public function update_robot_time()
  {
    session()->set('last_action_time', time());
  }

  public function send_sms()
  {
    return true;
  }

  /*生成验证码*/
  public function generate_captcha()
  {
    return rand(1000, 9999);
  }

  public function answers()
  {
    return $this
      ->belongsToMany('App\Answer')
      ->withPivot('vote')
      ->withTimestamps();
  }

  public function questions()
  {
    return $this
      ->belongsToMany('App\Question')
      ->withPivot('vote')
      ->withTimestamps();
  }

  public function exist()
  {
    return suc(['count' => $this->where(rq())->count()]);
  }
}
