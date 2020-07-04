<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Session;
use App\Admin;
use Hash;
use Image;

class AdminController extends Controller
{
    public function dashboard()
    {
        Session::put('page', 'dashboard');
        return view('admin.admin_dashboard');
    }

    public function settings()
    {
        Session::put('page', 'settings');
        // echo "<pre>";
        // print_r(Auth::guard('admin')->user());
        // die();
        $adminDetails = Admin::where('email', Auth::guard('admin')->user()->email)->first();
        return view('admin.admin_settings')->with(compact('adminDetails'));
    }

    public function login(Request $request)
    {
        // echo Hash::make('123456'); die(); 
        if ($request->isMethod('post')) {
            $data = $request->all();
            // echo "<pre>"; print_r($data); die();

            $rules = [
                'email' => 'required|email|max:255',
                'password' => 'required',
            ];

            $customMessages = [
                'email.required' => 'Email is required',
                'email.email' => 'Valid email is required',
                'password.required' => 'Password is required',
            ];

            $this->validate($request, $rules, $customMessages);

            if (Auth::guard('admin')->attempt(['email' => $data['email'], 'password' => $data['password']])) {
                return redirect('admin/dashboard');
            } else {
                Session::flash('error_message', 'Invalid Email or Password');
                return redirect()->back();
            }
        }

        return view('admin.admin_login');
    }

    public function logout()
    {
        Auth::guard('admin')->logout();
        return redirect('/admin');
    }

    public function checkCurrentPassword(Request $request)
    {
        $data = $request->all();

        if (Hash::check($data['current_pwd'], Auth::guard('admin')->user()->password)) {
            echo "true";
        } else {
            echo "false";
        }
    }

    public function updateCurrentPassword(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            // echo "<pre>";
            // print_r($data);
            // die();

            // check if current password is correct
            if (Hash::check($data['current_pwd'], Auth::guard('admin')->user()->password)) {
                // check if new and confirmed passwords match
                if ($data['new_pwd'] == $data['confirm_pwd']) {
                    Admin::where('id', Auth::guard('admin')->user()->id)->update(['password' => bcrypt($data['new_pwd'])]);
                    Session::flash('success_message', 'Password has been updated successfuly');
                } else {
                    Session::flash('error_message', 'New and Confirm Passwords dont match.');
                }
            } else {
                Session::flash('error_message', 'Your current password is incorrect.');
            }
            return redirect()->back();
        }
    }

    public function updateAdminDetails(Request $request)
    {
        Session::put('page', 'update-admin-details');
        $adminDetails = Admin::where('email', Auth::guard('admin')->user()->email)->first();

        if ($request->isMethod('post')) {
            $data = $request->all();
            // echo "<pre>";
            // print_r($data);
            // die();
            $rules = [
                'admin_name' => 'required|regex:/^[\pL\s\-]+$/u',
                'admin_mobile' => 'required|numeric',
                // 'admin_image' => 'image',
                // 'admin_image' => 'mimes:jpef,jpg,png,gif',
            ];

            $customMessages = [
                'admin_name.required' => 'Name is required',
                'admin_name.regex:/^[\pL\s\-]+$/u' => 'Valid name is required',
                'admin_mobile.required' => 'Mobile is required',
                'admin_mobile.numeric' => 'Valid mobile is required',
                // 'admin_image.image' => 'Valid image is required',

            ];
            $this->validate($request, $rules, $customMessages);

            // Upload Image
            if ($request->hasFile('admin_image')) {
                $image_tmp = $request->file('admin_image');
                if ($image_tmp->isValid()) {
                    // Get image extension
                    $extension = $image_tmp->getClientOriginalExtension();
                    // Generate new image
                    $imageName = rand(111, 99999) . '.' . $extension;
                    $imagePath = 'images/admin_images/admin_photos/' . $imageName;
                    // Upload image
                    Image::make($image_tmp)->save($imagePath);
                } else if (!empty($data['current_admin_image'])) {
                    $imageName = $data['current_admin_image'];
                } else {
                    $imageName = "";
                }

                // Image Update
                Admin::where('email', Auth::guard('admin')->user()->email)
                    ->update([
                        'image' => $imageName,
                    ]);
            }


            // Update admin details
            Admin::where('email', Auth::guard('admin')->user()->email)
                ->update([
                    'name' => $data['admin_name'],
                    'mobile' => $data['admin_mobile'],
                ]);
            Session::flash('success_message', 'Details Updated Successfuly');
            return redirect()->back();
        }
        return view('admin.update_admin_details')->with(compact('adminDetails'));
    }
}
