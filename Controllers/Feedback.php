<?php

namespace Controllers;

use Exception;

require_once('Core/Controller.php');

use Core\Controller as Controller;

class Feedback extends Controller
{


    //get alll comments
    public function GetAllComments($params)
    {
        try {
            $propertyId = (string)$params[0];

            $stmt = $this->execute($this->get('feedback','_id as id', "property_id='{$propertyId}'"));
                                
            $this->resolve(json_encode($stmt->fetchAll()),200);
                                
        } catch (Exception $err) {
            $this->reject('{
             "status": "500",
             "error": "true",
             "message": "' . $err->getMessage() . '"
             }
         }', 200);
        }
    }



    public function GetProperty($params)
    {
        try {
            $data = [
                'feedback.feedback as feedback',
                'feedback.created as created',
                'feedback.user_id as userId',
                'user.first_name as firstName',
                'user.last_name as lastName'
            ];
            $stmt = $this->execute($this->join('feedback',$data, "LEFT JOIN user ON feedback.user_id=user._id WHERE feedback._id='{$params[0]}'"));
            
            $this->resolve(json_encode($stmt->fetch()),200);
        } catch (Exception $err) {
            $this->reject('{
             "status": "500",
             "error": "true",
             "message": "' . $err->getMessage() . '"
             }
         }', 200);
        }
    }

    //Add new comment
    public function AddComment($params, $param)
    {
        try {

            $userId = (string)$param['userId'];
            $propertyId = (string)$param['propertyId'];

            if (!$this->authenticateUser($param['token'], $userId)) throw new Exception("Authentication failed.");
            $data = [
                'user_id' => (int)$param['anonymous'] == 0 ? $param['userId'] : 0,
                'property_id' => $propertyId,
                'feedback' => $param['feedback']
            ];

            $this->execute($this->save('feedback',$data));
            $this->resolve('{
                "action": "true",
                "message": "comment saved"
            }',201);

        } catch (Exception $err) {
            $this->reject('{
             "status": "500",
             "error": "true",
             "message": "' . $err->getMessage() . '"
             }
         }', 200);
        }
    }


    public function SaveReport($params, $param)
    {
        try {

            $userId = (string)$param['userId'];
            $propertyId = (string)$param['propertyId'];

            if (!$this->authenticateUser($param['token'], $userId)) throw new Exception("Authentication failed.");
            $data = [
                'user_id' => $userId,
                'property_id' => $propertyId,
                'reason' => $param['reason'],
                'message' => $param['message']
            ];

            $this->execute($this->save('report',$data));
            
            $this->reject('{
                "action": "true",
                "message": "Property reported."
            }',201);
        } catch (Exception $err) {
            $this->reject('{
             "status": "500",
             "error": "true",
             "message": "' . $err->getMessage() . '"
             }
         }', 200);
        }
    }

    public function post()
    {
        try {
            if (isset($this->params[0])) {
                if (!$this->authenticate()) throw new Exception("Unautherized request.");
                switch ($this->params[0]) {
                    case 'add':

                        
                        break;
                    case 'get':

                        switch ($this->params[1]) {
                            case 'all':

                                $stmt = $this->execute($this->get('feedback','_id as id', "property_id='{$this->secureParams['propertyId']}'"));
                                
                                $this->resolve(json_encode($stmt->fetchAll()),200);

                                break;
                            default:
                                $data = [
                                    'feedback.feedback as feedback',
                                    'feedback.created as created',
                                    'feedback.user_id as userId',
                                    'user.first_name as firstName',
                                    'user.last_name as lastName'
                                ];
                                $stmt = $this->execute($this->join('feedback',$data, "LEFT JOIN user ON feedback.user_id=user._id WHERE feedback._id='{$this->params[1]}'"));
                                
                                $this->resolve(json_encode($stmt->fetch()),201);
                                break;
                        }
                        break;

                    case 'report':

                        switch ($this->params[1]) {
                            case 'save':

                                $data = [
                                    'user_id' => $this->secureParams['userId'],
                                    'property_id' => $this->secureParams['propertyId'],
                                    'reason' => $this->secureParams['reason'],
                                    'message' => $this->secureParams['message']
                                ];

                                $stmt = $this->execute($this->save('report',$data));
                                
                                $this->reject('{
                                    "action": "true",
                                    "message": "Property reported."
                                }',201);

                                break;
                            case 'all':
                                $stmt = $this->execute($this->getAll('report'));
                                $this->resolve(json_encode($stmt->fetchAll()),200);
                                break;
                        }
                        break;

                    default:
                        throw new Exception("Invalid Request");
                }
            } else throw new Exception("Invalid Parmeters");
        } catch (Exception $err) {
            
            $this->reject('{
                "status": "500",
                "error": "true",
                "message": "' . $err->getMessage() . '"
        }',200);
        
        } //End of try catch

    } //End of post

    // Authenticate User 
    private function authenticate()
    {
        if (isset($this->secureParams['userId'], $this->secureParams['token'])) {
            if ($this->authenticateUser($this->secureParams['userId'], $this->secureParams['token'])) return true;
            else return false;
        } else return false;
    } //end of authenticateUser()

}//End of Class
