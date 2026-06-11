<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BriefPageStore;
use Illuminate\Contracts\View\View;

class UnlinkedPagesController extends Controller
{
    public function index(BriefPageStore $briefPages): View
    {
        return view('admin.unlinked-pages.index', [
            'staticPages' => $this->staticPages(),
            'briefPages' => $this->briefPages($briefPages),
        ]);
    }

    private function staticPages(): array
    {
        return [
            $this->page('Course pages', 'Postgraduate', 'courses.pg', 'Kept live; the updated Courses menu sends non-MBBS enquiries to Contact.'),
            $this->page('Course pages', 'Undergraduate', 'courses.ug', 'Kept live; the updated Courses menu sends non-MBBS enquiries to Contact.'),
            $this->page('Course pages', 'MBA', 'courses.mba', 'Kept live; the updated Courses menu sends non-MBBS enquiries to Contact.'),
            $this->page('Course pages', 'Doctoral', 'courses.doctoral', 'Kept live; the updated Courses menu sends non-MBBS enquiries to Contact.'),
            $this->page('Course pages', 'LLB', 'courses.llb', 'Kept live, but removed from the updated Courses menu.'),
            $this->page('Study-abroad pages', 'Study Abroad', 'study-abroad', 'No primary-nav item after the Services menu was removed.'),
            $this->page('Study-abroad pages', 'Test Preparation', 'services.test-prep', 'No primary-nav item after the Services menu was removed.'),
            $this->page('Study-abroad pages', 'Student Services', 'services.student-services', 'No primary-nav item after the Services menu was removed.'),
            $this->page('Study-abroad pages', 'Admissions Counselling', 'services.admissions-counselling', 'No primary-nav item after the Services menu was removed.'),
        ];
    }

    private function briefPages(BriefPageStore $briefPages): array
    {
        return array_map(function (array $page): array {
            $slug = (string) ($page['slug'] ?? '');
            $path = (string) ($page['path'] ?? ('/briefs/'.$slug));

            return [
                'group' => 'CMS-built brief pages',
                'title' => (string) ($page['title'] ?? 'Untitled'),
                'path' => $path,
                'url' => url($path),
                'route' => str_starts_with($path, '/briefs/') ? 'briefs.show' : 'custom path',
                'status' => (bool) ($page['visible'] ?? true) ? 'Live' : 'Hidden',
                'editable' => session('cms_super_admin') && $slug !== '',
                'edit_url' => $slug !== '' ? route('admin.pages.studio', $slug) : null,
                'note' => 'Managed in Page Builder; not part of the updated primary navigation.',
            ];
        }, $briefPages->all());
    }

    private function page(string $group, string $title, string $route, string $note): array
    {
        return [
            'group' => $group,
            'title' => $title,
            'path' => route($route, [], false),
            'url' => route($route),
            'route' => $route,
            'status' => 'Live',
            'editable' => false,
            'edit_url' => null,
            'note' => $note,
        ];
    }
}
