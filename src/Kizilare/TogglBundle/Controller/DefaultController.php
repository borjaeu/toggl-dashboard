<?php
namespace Kizilare\TogglBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    /**
     * @param int $offset
     *
     * @return Response
     */
    public function indexAction($offset)
    {
        $scheduleLoader = $this->get('kizilare.toggl.schedule_loader');
        $scheduleLoader->load($offset);

        return $this->render('KizilareTogglBundle:Default:index.html.twig', [
            'projects'    => $scheduleLoader->getProjects(),
            'slots'       => $scheduleLoader->getSlots(),
            'schedule'    => $scheduleLoader->getSchedule(),
            'estimation'  => $scheduleLoader->getEstimation(),
            'total'       => $scheduleLoader->getTotal(),
            'daily_total' => $scheduleLoader->getDailyTotal(),
            'habits'      => $this->getParameter('toggl.habits')
        ]);
    }
}
