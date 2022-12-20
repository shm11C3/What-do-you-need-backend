<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadPostImageRequest;
use App\Models\PostImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PostImageController extends Controller
{
    /**
     * @param PostImage $postImage
     */
    public function __construct(PostImage $postImage)
    {
        $this->postImage = $postImage;
    }

    /**
     * imageの登録処理
     *
     * @param UploadPostImageRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeImage(UploadPostImageRequest $request): \Illuminate\Http\JsonResponse
    {
        $auth_id = $request->subject;
        $image_group_uuid = $request['image_group_uuid']; // 1つのpostに関連するimagesグループのuuid

        if ($image_group_uuid === null) {
            // 1枚目のimage
            // 各値を設定
            $image_group_uuid = (string) Str::uuid();
            $image_number = 1;
            $post_ulid = null; // nullの値で宣言することで整合性を確保
        } else {
            $post_ulid = $request['post_ulid'];

            // image_group_idが存在する場合(2枚目以降の写真)
            $max_image_number = DB::table('post_images')
            ->where('image_group_uuid', $image_group_uuid)
            ->where('auth_id', $auth_id)
            ->where('post_ulid', $post_ulid) // postsに関連づいていない場合nullが代入されるため条件分岐する必要なし
            ->max('image_number');

            $image_number = $max_image_number + 1;

            // image_numberがimage_number_limit以上になること、
            // image_group_uuidが存在(2枚目以降のimage)している状態でimage_numberが1になることは
            // 仕様上ないため400エラーを返す
            if ($image_number > $this->postImage->image_number_limit || $image_number === 1) {
                abort(400);
            }
        }

        $uuid = (string) Str::uuid();

        try {
            DB::beginTransaction();

            DB::table('post_images')->insert([
                'uuid' => $uuid,
                'image_group_uuid' => $image_group_uuid,
                'post_ulid' => $post_ulid,
                'auth_id' => $auth_id,
                'image_number' => $image_number,
            ]);

            $uploaded_path = $this->postImage->uploadImageToS3($request['image'], $uuid);

            DB::commit();
        } catch (\Throwable $e) {
            Log::error($e);
            DB::rollBack();
            abort(500);
        }

        return response()->json([
            'uploaded_path' => $uploaded_path,
            'uuid' => $uuid,
            'image_group_uuid' => $image_group_uuid,
            'image_number' => $image_number,
        ]);
    }

    public function deleteImage()
    {

    }
}
