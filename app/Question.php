<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
  /*创建问题api*/
  public function add()
  {
    /*检查用户是否登录*/
    if (!user_ins()->is_logged_in())
      return ['status' => 0, 'msg' => 'login required'];

    /*检查是否存在标题*/
    if (!rq('title'))
      return ['status' => 0, 'msg' => 'required title'];

    $this->title = rq('title');
    $this->user_id = session('user_id');
    if (rq('desc')) // 如果存在描述就添加描述
      $this->desc = rq('desc');

    /*保存*/
    return $this->save() ?
      ['status' => 1, 'id' => $this->id] :
      ['status' => 0, 'msg' => 'db insert failed'];
  }

  /*更新问题api*/
  public function change()
  {
    /*检查用户是否登录*/
    if (!user_ins()->is_logged_in())
      return ['status' => 0, 'msg' => 'login required'];

    /*检查传参中是否有id*/
    if (!rq('id'))
      return ['status' => 0, 'msg' => 'id is required'];

    /*获取指定id的model*/
    $question = $this->find(rq('id'));

    /*判断问题是否存在*/
    if (!$question)
      return ['status' => 0, 'msg' => 'question not exists'];

    if ($question->user_id != session('user_id'))
      return ['status' => 0, 'msg' => 'permission denied'];

    if (rq('title'))
      $question->title = rq('title');

    if (rq('desc'))
      $question->desc = rq('desc');

    /*保存数据*/
    return $question->save() ?
      ['status' => 1] :
      ['status' => 0, 'msg' => 'db update failed'];
  }

  public function read_by_user_id($user_id)
  {
    $user = user_ins()->find($user_id);
    if (!$user)
      return err('user not exists');

    $r = $this->where('user_id', $user_id)
      ->get()->keyBy('id');

    return suc($r->toArray());
  }

  /*查看问题api*/
  public function read()
  {
    /*请求参数中是否有id, 如果有id直接返回id所在的行*/
    if (rq('id')) {
      $r = $this
        ->with('answers_with_user_info')
        ->find(rq('id'));
      return ['status' => 1, 'data' => $r];
    }

    if (rq('user_id')) {
      $user_id = rq('user_id') == 'self' ?
        session('user_id') :
        rq('user_id');
      return $this->read_by_user_id($user_id);
    }

    /*limit条件*/
    /*skip条件, 用于分页*/
    list($limit, $skip) = paginate(rq('page'), rq('limit'));

    /*构建query并返回collection数据*/
    $r = $this
      ->orderBy('created_at')
      ->limit($limit)
      ->skip($skip)
      ->get(['id', 'title', 'desc', 'user_id', 'created_at', 'updated_at'])
      ->keyBy('id');

    return ['status' => 1, 'data' => $r];
  }

  /*删除问题api*/
  public function remove()
  {
    /*检查用户是否登录*/
    if (!user_ins()->is_logged_in())
      return ['status' => 0, 'msg' => 'login required'];

    /*检查传参中是否有id*/
    if (!rq('id'))
      return ['status' => 0, 'msg' => 'id is required'];

    /*获取传参id所对应的model*/
    $question = $this->find(rq('id'));
    if (!$question) return ['status' => 0, 'question not exists'];

    /*检查当前用户是否为问题的所有者*/
    if (session('user_id') != $question->user_id)
      return ['status' => 0, 'permission denied'];

    return $question->delete() ?
      ['status' => 1] :
      ['status' => 0, 'msg' => 'db delete failed'];
  }

  public function user()
  {
    return $this->belongsTo('App\User');
  }

  public function answers()
  {
    return $this->hasMany('App\Answer');
  }

  public function answers_with_user_info()
  {
    return $this
      ->answers()
      ->with('user')
      ->with('users')
      ;
  }
}
