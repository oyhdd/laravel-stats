<?php

namespace Oyhdd\StatsCenter\Http\Controllers;

use Encore\Admin\Layout\Content;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    use HasResourceActions;

    protected $title = 'Title';
    public $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    /**
     * Set description for following 4 action pages.
     *
     * @var array
     */
    protected $description = [
       'index'  => 'Index',
       'show'   => 'Show',
       'edit'   => 'Edit',
       'create' => 'Create',
    ];

    /**
     * Index interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['index'])
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed   $id
     * @param Content $content
     *
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['show'])
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed   $id
     * @param Content $content
     *
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['edit'])
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     *
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['create'])
            ->body($this->form());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $model = $this->form()->model()->findOrFail($id);
        if (isset($model->status)) {
            $model->status = $model::STATUS_DELETED;
            $ret = $model->save();
        } else {
            $ret = $this->form()->destroy($id);
        }

        if ($ret) {
            $data = [
                'status'  => true,
                'message' => trans('admin.delete_succeeded'),
            ];
        } else {
            $data = [
                'status'  => false,
                'message' => trans('admin.delete_failed'),
            ];
        }

        return response()->json($data);
    }
}