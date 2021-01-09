<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if($request->has('user_id')) {

            $response = $this->repository->getUsersJobs($request->get('user_id'));

        }
        if($request->__authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $request->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID'))
        {
            $response = $this->repository->getAll($request);
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $response = $this->repository->store($request->__authenticatedUser, $request->all());

        return response($response);

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $cuser = $request->__authenticatedUser;
        $response = $this->repository->updateJob($id, array_except($request->all(), ['_token', 'submit']), $cuser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $adminSenderEmail = config('app.adminemail');

        $response = $this->repository->storeJobEmail($request->all());

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if($request->has('user_id')) {

            $response = $this->repository->getUsersJobsHistory($request->get('user_id'), $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($request->all(), $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {

        if($request->has('job_id')){
            $user = $request->__authenticatedUser;

            $response = $this->repository->acceptJobWithId($request->get('job_id'), $user);

            return response($response);
        }
        return null;
        
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $user = $request->__authenticatedUser;

        $response = $this->repository->cancelJobAjax($request->all(), $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $response = $this->repository->endJob($request->all());

        return response($response);

    }

    public function customerNotCall(Request $request)
    {
        $response = $this->repository->customerNotCall($request->all());

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $user = $request->__authenticatedUser;

        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();
        $distance = "";
        $time = "";
        $jobid = "";
        $session = "";
        $flagged = 'no';
        $manually_handled = 'no';
        $by_admin = 'no';
        $admincomment = "";

        // declear the valriables and the assign the requires values

        if (isset($data['distance'])) {
            $distance = $data['distance'];
        }

        if (isset($data['time'])) {
            $time = $data['time'];
        }

        if (isset($data['jobid'])) {
            $jobid = $data['jobid'];
        }
        
        if (isset($data['session_time'])) {
            $session = $data['session_time'];
        }
        
        if ($data['flagged'] == 'true') {
            if($data['admincomment'] == '') return "Please, add comment";
            $flagged = 'yes';
        }
        
        if ($data['manually_handled'] == 'true') {
            $manually_handled = 'yes';
        }
        
        if ($data['by_admin'] == 'true') {
            $by_admin = 'yes';
        }
        
        if (isset($data['admincomment'])) {
            $admincomment = $data['admincomment'];
        }

        //fine and update the records
        if ($time || $distance) {

            $affectedRows = Distance::where('job_id', $jobid)->update(['distance' => $distance, 'time' => $time]);
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {

            $affectedRows1 = Job::where('id', $jobid)->update(['admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin]);

        }

        return response('Record updated!');
    }

    public function reopen(Request $request)
    {
        $response = $this->repository->reopen($request->all());

        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}
