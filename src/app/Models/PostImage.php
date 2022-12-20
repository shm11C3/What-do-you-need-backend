<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PostImage extends Model
{
    use HasFactory;
    use HasUuids;

    protected $primaryKey = 'uuid';

    protected $keyType = 'string';

    /**
     * image_numberの許容最大値
     *
     * @var integer
     */
    public $image_number_limit = 4;

    /**
     * S3のディレクトリ名
     *
     * @var string
     */
    public $post_image_dir = 'posts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'post_ulid',
        'image_number',
        'image_group_uuid',
    ];

    /**
     * `post_images.uuid`から画像のパスを取得
     *
     * @param string $uuid
     * @return string
     */
    public function getImagePath(string $uuid): string
    {
        return $this->post_image_dir.'/'.$uuid.'.jpg';
    }

    /**
     * S3に画像ファイルをアップロード
     *
     * @param object $image
     * @param string $uuid
     * @return bool|string
     */
    public function uploadImageToS3(object $image, string $uuid): bool|string
    {
        $uploaded_path = Storage::disk('s3')->putFileAs($this->post_image_dir, $image, $uuid.'.jpg');

        // 値がfalse(Put失敗時)には例外を出力
        if (!$uploaded_path) {
            throw new Exception('S3 Put Error.');
        }

        return $uploaded_path;
    }
}
