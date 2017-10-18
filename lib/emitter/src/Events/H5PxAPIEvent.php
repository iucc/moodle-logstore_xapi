<?php namespace XREmitter\Events;
use \XREmitter\Repository as Repository;
use \stdClass as PhpObj;

class H5PxAPIEvent extends Event {
    protected static $verb_display;
    protected $repo;

    /**
    * Constructs a new Event.
    * @param repository $repo
    */
    public function __construct(Repository $repo) {
        $this->repo = $repo;
    }

    /**
    * Creates an event in the repository.
    * @param [string => mixed] $event
    * @return [string => mixed]
    */
    public function create(array $event) {
        return $this->repo->createEvent($event);
    }

    /**
    * Reads data for an event.
    * @param [String => Mixed] $opts
    * @return [String => Mixed]
    */
    public function read(array $opts) {
        $version = trim(file_get_contents(__DIR__.'/../../VERSION'));
        $version_key = 'https://github.com/LearningLocker/xAPI-Recipe-Emitter';
        $opts['context_info']->{$version_key} = $version;
        $xapi_statement = unserialize($opts['context_ext']['other']);

        // Hide some user data from LRS (privacy).
        unset($opts['context_ext']['other']);
        $opts['context_ext']['userid'] = $opts['user_name'];
        $opts['context_ext']['realuserid'] = $opts['user_name'];

        $arrRemove=array('category','sortorder','idnumber','summaryformat','format','showgrades','newsitems','startdate','marker','maxbytes','legacyfiles','showreports','visible','visibleold','groupmode',    'groupmodeforce','defaultgroupingid','lang','calendartype','theme','timecreated','timemodified','requested','enablecompletion','completionnotify','cacherev','type','component','crud','edulevel','contextid','contextlevel','contextinstanceid','userid','courseid','relateduserid','anonymous','other','origin','realuserid');

        foreach($arrRemove as $strName){

            if (isset($opts['context_ext']->$strName)){
                unset($opts['context_ext']->$strName);
            } 

            if (isset($opts['context_ext'][$strName])){
                unset($opts['context_ext'][$strName]);
            } 

            if (isset($opts['context_info']->$strName)){
                unset($opts['context_info']->$strName);
            }

        }


        $arrResult =[
            'actor' => $this->readUser($opts, 'user'),
            'verb' => $xapi_statement['statement']['verb'],
            'object' => $xapi_statement['statement']['object'],
            'result' => $xapi_statement['statement']['result'],
            'context' => [
                'platform' => $opts['context_platform'],
                'language' => $opts['context_lang'],
                'extensions' => [
                    $opts['context_ext_key'] => $opts['context_ext'],
                    'http://lrs.learninglocker.net/define/extensions/info' => $opts['context_info'],
                ],
                'contextActivities' => [
                    'grouping' => [
                        $this->readApp($opts)
                    ],
                    'category' => [
                        $this->readSource($opts)
                    ]
                ],
            ],
            'timestamp' => $opts['time'],
        ];

        unset($arrResult['context']['contextActivities']['category']);
        if (isset($arrResult['context']['contextActivities']['grouping'])){
            if (count($arrResult['context']['contextActivities']['grouping'])==1){
                unset($arrResult['context']['contextActivities']['grouping']);
            }else{
                unset($arrResult['context']['contextActivities']['grouping'][0]);
                $arrResult['context']['contextActivities']['grouping']=array_values($arrResult['context']['contextActivities']['grouping']);

                foreach  ($arrResult['context']['contextActivities']['grouping'] as $indexGroup => $arrGr){

                    if (isset($arrResult['context']['contextActivities']['grouping'][$indexGroup]['definition']['description'])){
                        unset($arrResult['context']['contextActivities']['grouping'][$indexGroup]['definition']['description']);
                    }

                    if (isset($arrResult['context']['contextActivities']['grouping'][$indexGroup]['definition']['summary'])){
                        unset($arrResult['context']['contextActivities']['grouping'][$indexGroup]['definition']['summary']);
                    }
                }
            }
        }



        return $arrResult;
    }

    protected function readUser(array $opts, $key) {
        return [
            'name' => $opts[$key.'_name'],
            'account' => [
                'homePage' => $opts[$key.'_url'],
                // Hide some user data from LRS (privacy).
                //'name' => $opts[$key.'_id'],
                'name' => $opts[$key.'_name'],
            ],
        ];
    }

    protected function readActivity(array $opts, $key) {
        $activity = [
            'id' => $opts[$key.'_url'],
            'definition' => [
                'type' => $opts[$key.'_type'],
                'name' => [
                    $opts['context_lang'] => $opts[$key.'_name'],
                ],
                //  'description' => [
                //                    $opts['context_lang'] => $opts[$key.'_description'],
                //                ],
            ],
        ];

        $arrRemove=array('category','sortorder','idnumber','summaryformat','format','showgrades','newsitems','startdate','marker','maxbytes','legacyfiles','showreports','visible','visibleold','groupmode',    'groupmodeforce','defaultgroupingid','lang','calendartype','theme','timecreated','timemodified','requested','enablecompletion','completionnotify','cacherev','type','component','crud','edulevel','contextid','contextlevel','contextinstanceid','userid','courseid','relateduserid','anonymous','other','origin','realuserid');

        if (isset($opts[$key.'_ext']) && isset($opts[$key.'_ext_key'])) {
            $activity['definition']['extensions'] = [];

            foreach($arrRemove as $strName){

                if (isset($opts[$key.'_ext']->$strName)){
                    unset($opts[$key.'_ext']->$strName);
                }  

            }

            if (isset($opts[$key.'_ext']->intro)){
                unset($opts[$key.'_ext']->intro);
            }

            if (isset($opts[$key.'_ext']->summary)){
                unset($opts[$key.'_ext']->summary);
            }

            if (isset($opts[$key.'_ext']->content)){
                unset($opts[$key.'_ext']->content);
            }

            if (isset($opts[$key.'_ext']->json_content)){
                unset($opts[$key.'_ext']->json_content);
            }

            if (isset($opts[$key.'_ext']->filtered)){
                unset($opts[$key.'_ext']->filtered);
            }

            if (isset($opts[$key.'_ext']->description)){
                unset($opts[$key.'_ext']->description);
            }

            $activity['definition']['extensions'][$opts[$key.'_ext_key']] = $opts[$key.'_ext'];

            if (isset($activity['definition']['description'])){
                unset($activity['definition']['description']);  
            }
        }

        return $activity;
    }

    protected function readCourse($opts) {
        return $this->readActivity($opts, 'course');
    }

    protected function readApp($opts) {
        return $this->readActivity($opts, 'app');
    }

    protected function readSource($opts) {
        return $this->readActivity($opts, 'source');
    }

    protected function readModule($opts) {
        return $this->readActivity($opts, 'module');
    }

    protected function readDiscussion($opts) {
        return $this->readActivity($opts, 'discussion');
    }

    protected function readQuestion($opts) {
        $opts['question_type'] = 'http://adlnet.gov/expapi/activities/cmi.interaction';
        $question = $this->readActivity($opts, 'question');

        $question['definition']['interactionType'] = $opts['interaction_type'];
        $question['definition']['correctResponsesPattern'] = $opts['interaction_correct_responses'];

        $supportedComponentLists = [
            'choice' => ['choices'],
            'sequencing' => ['choices'],
            'likert' => ['scale'],
            'matching' => ['source', 'target'],
            'performance' => ['steps'],
            'true-false' => [],
            'fill-in' => [],
            'long-fill-in' => [],
            'numeric' => [],
            'other' => []
        ];

        foreach ($supportedComponentLists[$opts['interaction_type']] as $index => $listType) {
            if (isset($opts['interaction_'.$listType]) && !is_null($opts['interaction_'.$listType])) {
                $componentList = [];
                foreach ($opts['interaction_'.$listType] as $id => $description) {
                    array_push($componentList, (object)[
                        'id' => (string) $id,
                        'description' => [
                            $opts['context_lang'] => $description,
                        ]
                    ]);
                }
                $question['definition'][$listType] = $componentList;
            }
        }
        return $question;
    }

    protected function readVerbDisplay($opts) {
        $lang = $opts['context_lang'];
        $lang = isset(static::$verb_display[$lang]) ? $lang : array_keys(static::$verb_display)[0];
        return [$lang => static::$verb_display[$lang]];
    }
}
