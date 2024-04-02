<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Post;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PostsController extends Controller
{
    public function create(Request $request)
    {

        $post = new Post;
        $post->user_id = Auth::user()->id;
        $post->desc = $request->desc;

        //check if post has photo
        if ($request->photo != '') {
            //choose a unique name for photo
            $photo = time() . '.jpg';
            file_put_contents('storage/posts/' . $photo, base64_decode($request->photo));
            $post->photo = $photo;
        }
        //mistake
        $post->save();
        $post->user;
        return response()->json([
            'success' => true,
            'message' => 'posted',
            'post' => $post
        ]);
    }


    public function update(Request $request)
    {
        $post = Post::find($request->id);
        // check if user is editing his own post
        // we need to check user id with post user id
        if (Auth::user()->id != $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'unauthorized access'
            ]);
        }
        $post->desc = $request->desc;
        $post->update();
        return response()->json([
            'success' => true,
            'message' => 'post edited'
        ]);
    }

    public function delete(Request $request)
    {
        $post = Post::find($request->id);
        // check if user is editing his own post
        if (Auth::user()->id != $post->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'unauthorized access'
            ]);
        }

        //check if post has photo to delete
        if ($post->photo != '') {
            Storage::delete('public/posts/' . $post->photo);
        }
        $post->delete();
        return response()->json([
            'success' => true,
            'message' => 'post deleted'
        ]);
    }

    public function posts()
    {
        $posts = Post::orderBy('id', 'desc')->get();

        foreach ($posts as $post) {
            // Định dạng lại created_at thành đối tượng Carbon
            $createdAt = Carbon::parse($post->created_at);

            // Tính thời gian kể từ created_at đến hiện tại
            $timeElapsed = $createdAt->diffInMinutes();

            // Xử lý định dạng thời gian
            if($timeElapsed < 1){
                $formattedTime = "Vừa xong";
            }
            elseif ($timeElapsed < 60) {
                // Nếu ít hơn 1 giờ, hiển thị theo phút
                $formattedTime = $timeElapsed . " phút trước";
            } elseif ($timeElapsed < 1440) {
                // Nếu ít hơn 1 ngày, hiển thị theo giờ
                $formattedTime = round($timeElapsed / 60) . " tiếng trước";
            } elseif ($timeElapsed < 1440 * 2) {
                // Nếu ít hơn 2 ngày, hiển thị "1 ngày trước"
                $formattedTime = "1 ngày trước";
            } else {
                // Nếu lớn hơn 2 ngày, hiển thị số ngày trước
                $formattedTime = round($timeElapsed / 1440) . " ngày trước";
            }

            // Lấy bình luận mới nhất liên quan đến bài đăng và kèm theo thông tin người dùng
            $latestComment = $post->comments()->latest()->with('user')->first();

            // Kiểm tra xem có bình luận không
            if ($latestComment) {
                // Lấy tên của người dùng từ bình luận mới nhất
                $latestCommentAuthorName = $latestComment->user->name;
                $latestCommentAuthorNameAvatar = $latestComment->user->photo;
                $latestCommentAuthorContent = $latestComment->comment;

                // Thêm thông tin về tên của người tạo bình luận vào mảng $post
                $post['latestCommentAuthorName'] = $latestCommentAuthorName;
                $post['latestCommentAuthorNameAvatar'] =  $latestCommentAuthorNameAvatar;
                $post['latestCommentAuthorContent'] =  $latestCommentAuthorContent;
            } else {
                // Nếu không có bình luận, đặt tên là null hoặc một giá trị mặc định khác
                $post['latestCommentAuthorName'] = null;
                $post['latestCommentAuthorNameAvatar'] = null ;
                $post['latestCommentAuthorContent'] = null ;
            }
            // Gán thời gian đã định dạng vào một trường mới
            $post['formattedTime'] = $formattedTime;

            // Xử lý các thông tin khác
            $post->user;
            $post['commentsCount'] = count($post->comments);
            $post['likesCount'] = count($post->likes);
            $post['selfLike'] = false;
            foreach ($post->likes as $like) {
                if ($like->user_id == Auth::user()->id) {
                    $post['selfLike'] = true;
                }
            }
        }

        return response()->json([
            'success' => true,
            'posts' => $posts
        ]);
    }



    public function myPosts()
    {
        $posts = Post::where('user_id', Auth::user()->id)->orderBy('id', 'desc')->get();
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'posts' => $posts,
            'user' => $user
        ]);
    }
}
