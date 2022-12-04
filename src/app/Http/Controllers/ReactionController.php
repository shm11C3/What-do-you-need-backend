<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddReactionRequest;
use App\Models\Reaction;
use App\Models\Post;

class ReactionController extends Controller
{
    /**
     * @param Reaction $reaction
     */
    public function __construct(Reaction $reaction)
    {
        $this->reaction = $reaction;
    }

    /**
     * リアクション一覧を返す
     *
     * @return Response Reaction::TYPES
     */
    public function getReactionTypes()
    {
        return response()->json(Reaction::TYPES);
    }

    /**
     * リアクションをDBに登録
     *
     * @param AddReactionRequest $request['post_ulid', 'reaction_type']
     * @return Response
     */
    public function addReaction(AddReactionRequest $request, string $reactableType)
    {
        $auth_id = $request->subject;

        // [TODO] この部分の処理（ユーザの存在チェック）をMiddlewareに任せたい
        if(!$request->user){
            return abort(401);
        }

        // リアクションの重複チェック
        if (!$this->reaction->isExistReactions(
            $request['reactable_ulid'], $auth_id, $request['reaction_type']
        )) {
            return abort(400);
        }

        // リアクションオブジェクトを生成
        $reaction = new Reaction([
            'auth_id' => $auth_id,
            'reaction_type' => $request['reaction_type'],
        ]);

        $reactable = null;

        if ($reactableType === 'post') {
            $reactable = Post::find($request['reactable_ulid']);
        }
        // Comment追加時に分岐を追加

        // 投稿が存在しない場合はBadRequestを返す
        if (!$reactable) {
            abort(400);
        }
        $reactable->reactions()->save($reaction);

        return response()->json(["status" => true, 'reaction' => $reaction]);
    }
}
