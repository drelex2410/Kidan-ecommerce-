<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogTranslation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view_all_blogs'])->only('index');
        $this->middleware(['permission:add_blog'])->only('create');
        $this->middleware(['permission:edit_blog'])->only('edit');
        $this->middleware(['permission:delete_blog'])->only('destroy');
        $this->middleware(['permission:publish_blog'])->only('change_status');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $sort_search = null;
        $blogs = Blog::orderBy('created_at', 'desc');

        if ($request->search != null) {
            $blogs = $blogs->where('title', 'like', '%' . $request->search . '%');
            $sort_search = $request->search;
        }

        $blogs = $blogs->paginate(15);

        return view('backend.blog.blogs.index', compact('blogs', 'sort_search'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $blog_categories = BlogCategory::all();
        $authors = User::orderBy('name')->get();

        return view('backend.blog.blogs.create', compact('blog_categories', 'authors'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $this->validateBlogRequest($request);

        $blog = new Blog;

        $this->fillSharedBlogFields($blog, $validated);
        $blog->title = $request->title;
        $blog->short_description = $request->short_description;
        $blog->description = $request->description;

        $blog->save();

        $blog_translation = BlogTranslation::firstOrNew(['lang' => env('DEFAULT_LANGUAGE'), 'blog_id' => $blog->id]);
        $blog_translation->title = $request->title;
        $blog_translation->short_description = $request->short_description;
        $blog_translation->description = $request->description;
        $blog_translation->hero_button_label = $request->hero_button_label;
        $blog_translation->modal_summary = $request->modal_summary;
        $blog_translation->save();

        flash(translate('Blog post has been created successfully'))->success();
        return redirect()->route('blog.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $blog = Blog::find($id);
        $blog_categories = BlogCategory::all();
        $authors = User::orderBy('name')->get();
        $lang = $request->lang;

        return view('backend.blog.blogs.edit', compact('blog', 'blog_categories', 'authors', 'lang'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validated = $this->validateBlogRequest($request, $id);

        $blog = Blog::find($id);

        $this->fillSharedBlogFields($blog, $validated);
        if ($request->lang == env("DEFAULT_LANGUAGE")) {
            $blog->title = $request->title;
            $blog->short_description = $request->short_description;
            $blog->description = $request->description;
        }

        $blog->save();

        $blog_translation = BlogTranslation::firstOrNew(['lang' => $request->lang, 'blog_id' => $blog->id]);
        $blog_translation->title = $request->title;
        $blog_translation->short_description = $request->short_description;
        $blog_translation->description = $request->description;
        $blog_translation->hero_button_label = $request->hero_button_label;
        $blog_translation->modal_summary = $request->modal_summary;
        $blog_translation->save();

        flash(translate('Blog post has been updated successfully'))->success();
        return redirect()->route('blog.index');
    }

    public function change_status(Request $request)
    {
        $blog = Blog::find($request->id);
        $blog->status = $request->status;

        $blog->save();
        return 1;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->blog_translations()->delete();

        Blog::destroy($id);
        flash(translate('Blog has been deleted successfully'))->success();
        return redirect('admin/blog');
    }

    protected function validateBlogRequest(Request $request, ?int $blogId = null): array
    {
        return $request->validate([
            'category_id' => ['required', 'exists:blog_categories,id'],
            'author_user_id' => ['nullable', 'exists:users,id'],
            'title' => ['required', 'max:255'],
            'slug' => ['required', 'max:255', Rule::unique('blogs', 'slug')->ignore($blogId)],
            'banner' => ['nullable'],
            'short_description' => ['nullable'],
            'description' => ['nullable'],
            'hero_button_label' => ['nullable', 'string', 'max:100'],
            'modal_summary' => ['nullable', 'string'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_img' => ['nullable'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string'],
        ]);
    }

    protected function fillSharedBlogFields(Blog $blog, array $validated): void
    {
        $blog->category_id = $validated['category_id'];
        $blog->author_user_id = $validated['author_user_id'] ?? null;
        $blog->banner = $validated['banner'] ?? null;
        $blog->slug = preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '-', $validated['slug']));
        $blog->published_at = $validated['published_at'] ?? null;
        $blog->meta_title = $validated['meta_title'] ?? null;
        $blog->meta_img = $validated['meta_img'] ?? null;
        $blog->meta_description = $validated['meta_description'] ?? null;
        $blog->meta_keywords = $validated['meta_keywords'] ?? null;
    }
}
