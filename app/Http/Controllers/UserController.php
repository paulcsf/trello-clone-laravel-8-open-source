<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use App\Http\Requests;
use \App\Models\User;
use \App\Models\Board;
use Validator;
use App\Helpers\Bot;


class UserController extends Controller
{
    protected $board;
    protected $user;

    public function __construct(Board $board, User $user)
    {
        $this->board = $board;
        $this->user = $user;
    }

    /**
     * Get the dashboard view
     * @return view home view
     */
    public function getDashboard()
    {
        Bot::sendMsg('someone open dashboard');

        $isMojri = true;
        if (env('USER_ADMIN_ID1') == Auth::id() || env('USER_ADMIN_ID2') == Auth::id()) {
            $departments = \App\Models\Department::with(['boards'])->get();
            $isMojri = false;
        } else {
            $departments = \App\Models\Department::with(['boards'])->where('owner_id', Auth::id())->get();
            if (sizeof($departments) > 0) {
                $isMojri = false;
            }
            //dd($departments->toArray());
        }
        $users = User::all();

        if ($isMojri == true) {
            $boards = $this->board->getUserBoards(Auth::id());
            $departments = $boards->first()->department()->get();
            $starredBoards = $this->board->getUserStarredBoards(Auth::id());
            return view('user.home', compact('boards', 'starredBoards', 'departments', 'isMojri', 'users'));
        } else {
            $boards = [];
            $starredBoards = [];
            return view('user.home', compact('boards', 'starredBoards', 'departments', 'isMojri', 'users'));
        }
    }

    /**
     * Get the board view
     * @return view board view
     */
    public function getBoard()
    {
        return view('user.board');
    }

    /**
     * Gets the user profile
     * @return view profile view
     */
    public function getProfile()
    {
        $boards = $this->board->getUserBoards(Auth::id());
        $page = 'profile';
        return view('user.profile', compact('boards', 'page'));
    }

    /**
     * Get the user login page or view.
     * @return view user login page or view
     */
    public function getLogin()
    {
        return view('auth.login');
    }

    /**
     * Validate the user login input data against the user data in database.
     * @param  Request $request have the input data for this function
     * @return view reirect to specific view
     */
    public function postLogin(Request $request)
    {
        $this->validate($request, [
            'email'    => 'required',
            'password' => 'required',
        ]);


        if (!Auth::attempt(['email' => $request->get('email'), 'password' => $request->get('password')], $request->get('remember'))) {
            return redirect()->back()->with('alert', 'Can\'t log you in with given information.');
        }

        return redirect()->route('user.dashboard')->with('info', 'You are logged in.');
    }

    /**
     * Get the user register view
     * @return view user register view or page
     */
    public function getRegister()
    {
        return view('auth.register');
    }

    /**
     * Create a new user account or register a user into our website "Dingo"
     * @param  Request $request has the user registeration data
     * @return view reirect to specific view
     */
    public function postRegister(Request $request)
    {
        $this->validate($request, [
            'name'                  => 'required|unique:users,name',
            'email'                 => 'required|unique:users,email',
            'password'              => 'required|confirmed',
            'password_confirmation' => 'required',
        ]);

        $this->user->createUserAccount($request);

        return redirect()->route('auth.login')->with('alert', 'Your account has been created.');
    }
}
