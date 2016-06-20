<?php
namespace Kizilare\TogglBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class SwitchController extends Controller
{
    /**
     * @param Request $request
     * @param         $newContext
     *
     * @return RedirectResponse
     */
    public function indexAction(Request $request, $newContext)
    {
        $backTo = $request->query->get('back_to');
        $this->get('kizilare.toggl.api')->switchTask($newContext);

        if ($backTo) {
            return $this->redirect($backTo);
        }

        return $this->redirectToRoute('toggl_homepage');
    }

    /**
     * @param $newProject
     *
     * @return RedirectResponse
     */
    public function projectAction($newProject)
    {
        $this->get('kizilare.toggl.api')->switchTask('fill me', $newProject);

        return $this->redirectToRoute('toggl_homepage');
    }
}
