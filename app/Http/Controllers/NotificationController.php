<?php

namespace App\Http\Controllers;

use App\Notification;
use App\Http\Library\Responses;
use App\Http\Traits\LoggedUser;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NotificationController extends Controller
{
    use LoggedUser;

    /**
     * Daftar notifikasi milik user yang sedang login, terbaru duluan.
     * Dipakai untuk isi dropdown lonceng notifikasi.
     */
    public function index(Request $request)
    {
        $userData = $this->get();
        $user     = $userData['user'];

        $query = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        // $request->boolean() belum tersedia di versi Illuminate yang dipakai Lumen 5.6
        // (baru ada di Laravel versi lebih baru), jadi dicek manual biar gak fatal error.
        $unreadOnly = filter_var($request->input('unread_only', false), FILTER_VALIDATE_BOOLEAN);
        if ($unreadOnly) {
            $query->where('is_read', false);
        }

        $perPage = $request->input('per_page', 20);
        $data    = $query->paginate($perPage);

        return Responses::sendResponse($data, 'Notifikasi Retrieved Successfully');
    }

    /**
     * Jumlah notifikasi belum dibaca milik user yang sedang login.
     * Dipakai buat badge angka merah di ikon lonceng, di-poll berkala oleh FE.
     */
    public function unreadCount()
    {
        $userData = $this->get();
        $user     = $userData['user'];

        $count = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return Responses::sendResponse(['unread_count' => $count], 'Unread Count Retrieved Successfully');
    }

    /**
     * Tandai 1 notifikasi sudah dibaca. Dipanggil saat user klik salah satu
     * item di dropdown notifikasi (biasanya sekalian dipakai untuk buka detailnya).
     */
    public function markRead($id)
    {
        $userData = $this->get();
        $user     = $userData['user'];

        $notification = Notification::where('id', $id)->where('user_id', $user->id)->first();
        if (!$notification) {
            return Responses::sendError([], 'Notifikasi Not Found');
        }

        if (!$notification->is_read) {
            $notification->is_read = true;
            $notification->read_at = Carbon::now();
            $notification->save();
        }

        return Responses::sendResponse($notification, 'Notifikasi Ditandai Sudah Dibaca');
    }

    /**
     * Tandai SEMUA notifikasi milik user yang sedang login sebagai sudah dibaca.
     * Dipakai untuk tombol "Tandai semua sudah dibaca" di dropdown.
     */
    public function markAllRead()
    {
        $userData = $this->get();
        $user     = $userData['user'];

        Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => Carbon::now()]);

        return Responses::sendResponse([], 'Semua Notifikasi Ditandai Sudah Dibaca');
    }
}