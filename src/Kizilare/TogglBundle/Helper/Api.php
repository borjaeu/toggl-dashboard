<?php
namespace Kizilare\TogglBundle\Helper;

class Api
{
    /**
     * @var string
     */
    protected $apiKey;

    /**
     * @var string
     */
    protected $apiUrl;

    /**
     * @var int
     */
    protected $workspaceId;

    /**
     * Api constructor.
     *
     * @param string $apiKey
     * @param string $apiUrl
     * @param int    $workspaceId
     */
    public function __construct($apiKey, $apiUrl, $workspaceId)
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
        $this->workspaceId = $workspaceId;
    }

    protected function getRunningProjectId()
    {
        $entry = $this->getCurrentTimeEntry();
        if (false == $entry) {
            return 0;
        }
        return $entry['pid'];
    }

    public function switchTask($message, $projectId = null)
    {
        $currentTask = $this->getCurrentTimeEntry();
        if ($projectId == null) {
            $projectId = $currentTask['pid'];
        }

        if ($currentTask['description'] !== $message || $currentTask['pid'] !== $projectId) {
            $newData = [
                'time_entry' => [
                    'description'  => $message,
                    'pid'          => $projectId,
                    'created_with' => 'Toggl tool'
                ]
            ];
            $this->requestApi('time_entries/start', $newData);
        }
    }

    public function getUser()
    {
        return $this->requestApi('me');
    }

    public function getWorkspaces()
    {
        $workspacesData = $this->requestApi('workspaces');
        if (!empty($workspacesData)) {
            return $this->cleanCollection($workspacesData, array('id' => 0, 'name' => ''), 'id');
        }
        return [];
    }

    public function getProjects()
    {
        $clientsData = $this->getClients();
        $projectsData = $this->requestApi('workspaces/' . $this->workspaceId . '/projects');
        if (!empty($projectsData)) {
            $projectsData = $this->cleanCollection($projectsData, array('id' => 0, 'name' => '', 'cid' => ''), 'id');
        }

        foreach ($projectsData as & $projectItem) {
            $projectItem['client'] = $clientsData[$projectItem['cid']]['name'];
            unset($projectItem['cid']);
        }

        return $projectsData;
    }

    public function getClients()
    {
        $clientsData = $this->requestApi('workspaces/' . $this->workspaceId . '/clients');

        if (!empty($clientsData)) {
            return $this->cleanCollection($clientsData, array('id' => 0, 'name' => ''), 'id');
        }
        return [];
    }

    public function getWeekDetails($date)
    {
        $projects = $this->getProjects();

        $week = date('W', $date);
        $year = date('Y', $date);
        $from = date("Y-m-d", strtotime("{$year}-W{$week}-1"));
        $to = date("Y-m-d", strtotime("{$year}-W{$week}-7"));
        $params = http_build_query([
            'start_date'    => $from . 'T00:00:00+01:00',
            'end_date'      => $to . 'T23:59:59+01:00'
        ]);
        $entries_data = $this->requestApi('time_entries?' . $params);

        foreach ($entries_data as & $entry) {
            if (isset($entry['pid'])) {
                $entry['project'] = $projects[$entry['pid']]['name'];
                $entry['project_id'] = $entry['pid'];
            } else {
                $entry['project'] = 'Unknown';
                $entry['project_id'] = 0;
            }
        }

        $entries_data = $this->cleanCollection($entries_data, [
            'id' => 0,
            'start' => '',
            'stop' => '',
            'description' => '',
            'project' => '',
            'project_id' => ''
        ]);
        return $entries_data;
    }

    public function getCurrentTimeEntry()
    {
        $entry = $this->requestApi('time_entries/current');
        if (empty($entry['data'])) {
            return false;
        }
        return $this->cleanData($entry['data'], ['id' => null, 'pid' => 0, 'description' => '']);
    }

    /**
     * @param string $url
     * @param null $data
     *
     * @return mixed
     */
    protected function requestApi($url, $data = null)
    {
        $url = $this->apiUrl . $url;
        $curl_handler = curl_init($url);
        curl_setopt($curl_handler, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handler, CURLOPT_SSL_VERIFYPEER, false);
        if ($data) {
            curl_setopt($curl_handler, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($curl_handler, CURLOPT_POST, 0);
        }
        curl_setopt($curl_handler, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($curl_handler, CURLOPT_USERPWD, $this->apiKey . ':api_token');

        $response = curl_exec($curl_handler);

        return json_decode($response, true);
    }

    protected function cleanCollection($collection, $valid_items, $use_key = false)
    {
        $result = array();
        foreach ($collection as $key => $data) {
            $key = $use_key ? $data[$use_key] : $key;
            $result[$key] = $this->cleanData($data, $valid_items);
        }
        return $result;
    }

    protected function cleanData($data, $valid_items)
    {
        $result = array();
        foreach ($valid_items as $field => $default_value) {
            $result[$field] = isset($data[$field]) ? $data[$field] : $default_value;
        }
        return $result;
    }
}
