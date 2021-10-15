<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    //die( getcwd());

    require_once 'defines/CDH_awsDefinesAll.php';
    require_once 'classes/CDH_abstractAwsCloudator.php';
    require_once 'classes/CDH_awsBucket.php';       
    require_once 'classes/CDH_awsImage.php';
    require_once 'classes/CDH_awsInstance.php';
    require_once 'classes/CDH_awsInstanceProfile.php';   
    require_once 'classes/CDH_awsKeyPair.php';
    require_once 'classes/CDH_awsPolicy.php';   
    require_once 'classes/CDH_awsRole.php';   
    require_once 'classes/CDH_awsSecurityGroup.php';
    require_once 'classes/CDH_awsSubnet.php';
    require_once 'classes/CDH_awsDBCluster.php';
    //require_once 'classes/CDH_PS_userDataChecker.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsStep3ImageSystem extends CDH_abstractAwsCloudator{
        
        
        #private $subnet0Name;
        #private $subnet1Name;
        #private $subnet2Name;
        

        public function __construct(){
            /*
            if ( touch( CDH_IMAGE_FILE_USER_DATA ) == false){
                $this->logUser('{"five":"\nL035 No touched file. " . CDH_IMAGE_FILE_USER_DATA . "\n";
                die('\nL036 Problemas touching\n');
            }
            */

            $this->createNames();

        }

    
        public function createForCloudator() {
            
            #region Policy
            $service = $this->createPolicy();
            if( $service == false ) {
                $this->logUser('{"five":"\nL051 - Policy no creado\n"},');
                return false;
            }
            $this->logUser('{"five":"\nL054 - Policy creado\n"},');
            #endregion Policy
     
            #region Role
            $service = $this->createRole(); 
            if( $service == false ) {
                $this->logUser('{"five":"\nL060 - Role no creado\n"},');
                return false;
            }
            $this->logUser('{"five":"\nL063 - Role creado\n"},');                       
            #endregion Role
            
            #region InstanceProfile
            $service = $this->createInstanceProfile(); 
            if( $service == false ) {
                $this->logUser('{"five":"\nL069 - InstanceProfile no creado\n"},');
                return false;
            }
            $this->logUser('{"five":"\nL072 - InstanceProfile creado\n"},');
            #endregion InstanceProfile
            
            #region addRoleToInstanceProfile
            $service = $this->addRoleToInstanceProfile(); 
            if( $service == false ) {
                $this->logUser('{"five":"\nL078 - addRoleToInstanceProfile no creado\n"},');
                return false;
            }
            $this->logUser('{"five":"\nL081 - addRoleToInstanceProfile creado\n"},');           
            #endregion addRoleToInstanceProfile        
            
            #region attachRolePolicy
            $service = $this->attachRolePolicy(); 
            if( $service == false ) {
                $this->logUser('{"five":"\nL106 - attachRolePolicy Service no creado\n"},');
                return false;
            }
            $this->logUser('{"five":"\nL109 - attachRolePolicy Service creado\n"},');
            #endregion attachRolePolicy
            
            #region createSecurityGroupAmi
            $service = $this->createSecurityGroupAmi(); 
            if( $service == false ) {
                $this->logUser('{"five":"\nL087 - SecurityGroupAmi no creado\n"},');
                return false;
            }
            $this->logUser('{"five":"\nL076 - SecurityGroupAmi creado\n"},');      
           
            #endregion createSecurityGroupAmi
            
            #region createKeyPair
            $service = $this->createKeyPair(); 
            if( $service == false ) {
                $this->logUser('{"five":"\nL096 - KeyPair Service no creado\n"},');
                return false;
            }
            $this->logUser('{"five":"\nL098 - KeyPair Service creado\n"},');
            #endregion createKeyPair
         
            #region createuserdatafile
            $service = $this->createUserDataFile(); 
            if( $service == false ) {
                $this->logUser('{"five":"\nL107 - UserDataFile no creado\n"},');
                return false;
            }
            #endregion createuserdatafile
            
            #region createInstance
            $service = $this->createInstance(); 
            if( $service == false ) {
                $this->logUser('{"five":"\nL105 - Instance Service no creado\n"},');
                return false;
            }
            $this->logUser('{"five":"\nL108 - Instance creado\n"},');            
            #endregion createInstance
           
            #region createImage
            $service = $this->createImage(); 
            if( $service == false ) {
                $this->logUser('{"five":"\nL126 - Image Service no creado\n"},');
                return false;
            }
            $this->logUser('{"five":"\nL129 - Image Service creado\n"},');           
            #endregion createImage
            
            
            #region deleteServices
            $service = $this->deleteServices(); 
            if( $service == false ) {
                $this->logUser('{"five":"\nL121 - Delete Services no completado\n"},');
                return false;
            }
            $this->logUser('{"five":"\nL124 - Delete Services completado\n"},');            
            #endregion deleteServices
            
            
            $this->logUser('{"five":"\nL096 - TODO IMAGE AMI creado\n"},');

            return true;
                        
        }


        public function createPolicy(){
            $service        = new CDH_awsPolicy();
            $data           = CDH_IMAGE_POLICY;

            $client = $service->getClientIam();

    	    #return true;
            $service        = $service->create( $data );
            if( $service == false){
                $this->logUser('{"five":"\nL141 - Policy no creado\n"},');
                return false;
            }            
            return true;
        }

        public function createRole(){
            $service        = new CDH_awsRole();
            $data           = CDH_IMAGE_ROLE;
            #return true;
            $service        = $service->create( $data );
            if( $service == false){
                $this->logUser('{"five":"\nL154 - Role no creado\n"},');
                return false;
            }
            
            return true;
        }

        public function createInstanceProfile(){
            $service = new CDH_awsInstanceProfile();
            $data    = CDH_IMAGE_INSTANCE_PROFILE;
            #return true;
            $service        = $service->create( $data );
            if( $service == false){
                $this->logUser('{"five":"\nL167 - Role no creado\n"},');
                return false;
            }
            
            return true;
        }

        public function addRoleToInstanceProfile(){
            $service        = new CDH_awsRole();
            $data           = CDH_IMAGE_ROLE;

            $service = $service->addRoleToInstanceProfile( CDH_IMAGE_addRoleToInstanceProfile );
            if( $service == false){
                $this->logUser('{"five":"\nL178 - Role no agregaado\n"},');
                return false;
            }

            return true;

        }

        public function attachRolePolicy(){
            $service    =  new CDH_awsRole();
            $policy     = new CDH_awsPolicy();
            $policy     = $policy->getServiceByName( CDH_IMAGE_POLICY['PolicyName'] );  
            #return true;
            if( !isset( $policy['Arn'] ) ) {
                $this->logUser('{"five":"\nL202 - NO hay Policy ARN\n"},');
                return false;
            }
            
            $CDH_IMAGE_attachRolePolicy = CDH_IMAGE_attachRolePolicy;
            $CDH_IMAGE_attachRolePolicy['PolicyArn'] = $policy['Arn'];
            $service->attachRolePolicy ( $CDH_IMAGE_attachRolePolicy  );

            return true;

        }




        public function createSecurityGroupAmi(){
            $service        = new CDH_awsSecurityGroup();
            $vpc            = new CDH_awsVpc();
            $data           = CDH_IMAGE_SECURITY_GROUP;
            
            $data['VpcId']  = $vpc->getIdByName( CDH_VPC_NAME );
            /*$this->logUser('{"five":CDH_VPC_NAME . "\n";
            print_r($data);die();*/
            #return true;
            $service            = $service->create( $data );
            if( $service == false){
                $this->logUser('{"five":"\nL197 - SG no creado\n"},');
                return false;
            }

            return true;     
        }

        public function createKeyPair(){
            $service        = new CDH_awsKeyPair();
            $service        = $service->create( CDH_IMAGE_KEYPAIR, CDH_IMAGE_KEYPAIR_sshAccessFilePath );
            #return true;
            if( $service == false){
                $this->logUser('{"five":"\nL210 - KEYPAIR no creado\n"},');
                return false;
            }

            return true;

        }


        public function createUserDataFile(){
            
            $fileSystem = new CDH_awsFileSystem();
            $dbCluster  = new CDH_awsDBCluster();
            $image      = new CDH_awsImage();
            //$userData   = new CDH_PS_userDataChecker();

            $CDH_IMAGE_UserDataFile                               = CDH_IMAGE_UserDataFile;

            $CDH_IMAGE_UserDataFile["{cdh_dominio_sin_punto}"           ] = CDH_DOMINIO_SIN_PUNTO;
            $CDH_IMAGE_UserDataFile["{cdh_dominio_con_punto}"           ] = CDH_DOMINIO_CON_PUNTO;
            $CDH_IMAGE_UserDataFile["{cdh_dominio_con_www}"             ] = CDH_DOMINIO_CON_WWW;

            $CDH_IMAGE_UserDataFile["{cdh_aws_region}"          ] = CDH_AWS_REGION;
            $CDH_IMAGE_UserDataFile["{cdh_dir_efs}"             ] = CDH_DIR_EFS ;

            $CDH_IMAGE_UserDataFile["{cdh_ftp_user}"            ] = "cdh_" . CDH_DOMINIO_CON_PUNTO;
            $CDH_IMAGE_UserDataFile["{cdh_ftp_password}"        ] = "cdh_" . CDH_DOMINIO_SIN_PUNTO . "1234";
            $CDH_IMAGE_UserDataFile["{cdh_metadata_ip}"         ] = CDH_METADATA_IP;

            $CDH_IMAGE_UserDataFile["{cdh_name_cms}"            ] = CDH_NAME_CMS;
            $CDH_IMAGE_UserDataFile["{cdh_version_cms}"         ] = CDH_VERSION_CMS;
            $CDH_IMAGE_UserDataFile["{cdh_php_version_cms}"     ] = CDH_PHP_VERSION_CMS;

            $CDH_IMAGE_UserDataFile["{cdh_apache_user}"         ] = CDH_APACHE_USER;
            $CDH_IMAGE_UserDataFile["{cdh_apache_group}"        ] = CDH_APACHE_GROUP;

            $CDH_IMAGE_UserDataFile["{cdh_aws_profile}"          ] = CDH_AWS_PROFILE;
            $CDH_IMAGE_UserDataFile["{cdh_aws_access_key}"       ] = CDH_AWS_ACCESS_KEY;//; $userData->getAwsAccessKeyId(); #"AKIA4RANIPTFY2SRSHDA";
            $CDH_IMAGE_UserDataFile["{cdh_aws_access_key_secret}"] = CDH_AWS_ACCESS_KEY_SECRET;//$userData->getAwsSecretAccessKey(); #"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";
    
            $CDH_IMAGE_UserDataFile["{cdh_endpoint_filesystem}" ] = $fileSystem->getFileSystemEndPointByName( CDH_FILESYSTEM['Tags'][0]['Value'], CDH_AWS_REGION );
            
            $CDH_IMAGE_UserDataFile["{cdh_bbdd_user}"     ] = CDH_BBDD_USER;// $userData->getDBMasterUsername();
            $CDH_IMAGE_UserDataFile["{cdh_bbdd_passwd}"   ] = CDH_BBDD_PASSWD;//$userData->getDBMasterUserPassword();
            $CDH_IMAGE_UserDataFile["{cdh_bbdd_name}"     ] = CDH_BBDD_NAME;//$userData->getDBName();
            $CDH_IMAGE_UserDataFile["{cdh_bbdd_endpoint}" ] = $dbCluster->getEndPointWriter( CDH_DB_INSTANCE_createDBCluster['DBClusterIdentifier'] );;

            $CDH_IMAGE_UserDataFile["{cdh_image_bucket_name}"   ] = CDH_IMAGE_BUCKET_NAME;
            
            $CDH_IMAGE_UserDataFile["{cdh_php_ini}"                      ] = CDH_PHP_INI;//$userData->getCmsName();
            $CDH_IMAGE_UserDataFile["{cdh_documentroot}"                 ] = CDH_DOCUMENTROOT;//$userData->getCmsName();

            $CDH_IMAGE_UserDataFile["{cdh_apache_site_available_destiny}"]  = CDH_APACHE_SITE_AVAILABLE_DESTINY;
            $CDH_IMAGE_UserDataFile["{cdh_apache_log_dir}"]                 = CDH_APACHE_LOG_DIR;

            
            #"prestashop";
            //$this->logUser('{"five":$userData->getCmsName();die();
            #$CDH_IMAGE_UserDataFile["{cdh_version_cms}"             ] = CDH_CMS_VERSION_CMS;//$userData->getCmsVersion(); #"1.7.7.6";
            #$CDH_IMAGE_UserDataFile["{cdh_php_version}"             ] = CDH_CMS_PHP_VERSION;//$userData->getPhpVersion(); #"7.4";
            

            

//var_dump($CDH_IMAGE_UserDataFile);die();
            $res = $image->createUserDataFile( $CDH_IMAGE_UserDataFile );

            if( $res == true)
                return true;
            
            $this->logUser('{"five":"\nError creando user data file\n"},');
            return false;
        }

        
        public function createInstance(){
            $instance   = new CDH_awsInstance();
            $bucket     = new CDH_awsBucket();
            $ip         = new CDH_awsInstanceProfile();
            $sg         = new CDH_awsSecurityGroup();
            $subnet     = new CDH_awsSubnet();
            $image      = new CDH_awsImage();
        
            $ipService      = $ip->getServiceByName     (   CDH_IMAGE_INSTANCE_PROFILE['InstanceProfileName'] );
            $ipArn          = $ipService['Arn'];

            $sgService      = $sg->getServiceByName     (   CDH_IMAGE_SECURITY_GROUP  ['GroupName']  );
            $sgId           = $sgService['GroupId'];

            $subnetService  = $subnet->getServiceByName (   $this->getSubnetPublic0Name()       );
            $subnetId       = $subnetService['SubnetId'];


            #$instance->deleteByTagName( 'CDH_IMAGE_' . CDH_DOMINIO_SIN_PUNTO );


            $CDH_IMAGE_INSTANCE                                         = CDH_IMAGE_INSTANCE;
            $CDH_IMAGE_INSTANCE['IamInstanceProfile']['Arn']            = $ipService['Arn'];
            $CDH_IMAGE_INSTANCE['NetworkInterfaces'][0]['Groups'][0]    = $sgId;
            $CDH_IMAGE_INSTANCE['NetworkInterfaces'][0]['SubnetId']     = $subnetId;
            
           /* var_export( $CDH_IMAGE_INSTANCE );
            die("L343");//return true;*/
            
            $instances  = $instance->create( $CDH_IMAGE_INSTANCE );
            #$instances  = true;
            $this->logUser('{"five":"\nL342\n"},'); 
            if( $instances == false || !isset($instances[0]['InstanceId']) )
                return false;
                
            $instanceId = $instances[0]['InstanceId'];
            
            $instanceName = $CDH_IMAGE_INSTANCE['TagSpecifications'][0]['Tags'][0]['Value'];

            #$instanceId = $instance->getIdByName( $instanceName );

            $starts = CDH_IMAGE_BUCKET_ROOT_NAME;
            #$name   = $bucket->getLastBucketBeginsWith( $starts  );               
            $name   = CDH_IMAGE_BUCKET_NAME;
            $key    = CDH_IMAGE_BUCKET_TAG_KEY;
            $value  = CDH_IMAGE_BUCKET_TAG_VALUE; 
            $this->logUser('{"five":"\nL325 - \$instanceId = $instanceId | \$name = $name \$key = $key \$value = $value\n"},');
            #die( "\n\$name = $name \$key = $key \$value = $value\n" );
            if($image->waitInstanceUserDataFinished( $name, $key, $value ) == false){
                $this->logUser('{"five":"\nL328 BBB = false\n"},');
                return false;
            }
            $this->logUser('{"five":"\nL328 BBB = true\n"},');

            #return $instanceId;
            return true;
        }





        public function createImage(){
            $service    = new CDH_awsImage();
            $instance   = new CDH_awsInstance();

            $instanceName = CDH_IMAGE_INSTANCE['TagSpecifications'][0]['Tags'][0]['Value'];

            


            $instanceId = $instance->getIdByName( $instanceName );
            
            #die( "\n\$instanceId = $instanceId" );


            $CDH_IMAGE = CDH_IMAGE;

            $CDH_IMAGE['InstanceId'] = $instanceId;

            #print_r( $CDH_IMAGE );
            #die( "\nL386" );
            if( $service->create( $CDH_IMAGE ) == false)
                return false;

            return true;

        }

        public function deleteServices(){
            $bucket             = new CDH_awsBucket();
            $instance           = new CDH_awsInstance();
            $keyPair            = new CDH_awsKeyPair();
            $instanceProfile    = new CDH_awsInstanceProfile();
            $policy             = new CDH_awsPolicy();
            $role               = new CDH_awsRole();
            $securityGroup      = new CDH_awsSecurityGroup();
           
            $instanceId         = $instance->getIdByName        ( CDH_IMAGE_INSTANCE        ['TagSpecifications'][0]['Tags'][0]['Value']);
            $keyPairId          = $keyPair->getIdByName         ( CDH_IMAGE_KEYPAIR         ['TagSpecifications'][0]['Tags'][0]['Value']);
            $instanceProfileId  = $instanceProfile->getIdByName ( CDH_IMAGE_INSTANCE_PROFILE['InstanceProfileName']                     );
            $policyId           = $policy->getIdByName          ( CDH_IMAGE_POLICY          ['PolicyName']                              );
            $roleId             = $role->getIdByName            ( CDH_IMAGE_ROLE            ['RoleName']                                );
            $securityGroupId    = $securityGroup->getIdByName   ( CDH_IMAGE_SECURITY_GROUP  ['GroupName']                               );
            
            $policyService = $policy->getServiceByName(         CDH_IMAGE_POLICY          ['PolicyName']);
            $policyArn      = $policyService['Arn'];
            
            $this->logUser('{"five":"\n\$instanceId        = $instanceId"},');
            $this->logUser('{"five":"\n\$keyPairId         = $keyPairId"},');
            $this->logUser('{"five":"\n\$instanceProfileId = $instanceProfileId"},');
            $this->logUser('{"five":"\n\$policyArn         = $policyArn"},');
            $this->logUser('{"five":"\n\$roleId            = $roleId"},');
            $this->logUser('{"five":"\n\$securityGroupId   = $securityGroupId"},');
            
            $bucket->delete ( CDH_IMAGE_BUCKET_NAME               );
            $this->logUser('{"five":"\n L433 Bucket borrado\n"},');

            $res = $instance->delete( CDH_IMAGE_INSTANCE ['TagSpecifications'][0]['Tags'][0]['Value'] );
            if( $res == false )
                $this->logUser('{"five":"\nInstance $instanceId no borrada\n"},');
            
            $res = $keyPair->delete( CDH_IMAGE_KEYPAIR  ['TagSpecifications'][0]['Tags'][0]['Value'] );
            if( $res == false )
                $this->logUser('{"five":"\nkeyPair $keyPairId no borrada\n"},');
            
            $CDH_IMAGE_detachRolePolicy = CDH_IMAGE_detachRolePolicy;
            $CDH_IMAGE_detachRolePolicy['PolicyArn'] = $policyArn;

            $res = $role->detachRolePolicy( $CDH_IMAGE_detachRolePolicy );
            if( $res == false )
                $this->logUser('{"five":"\nRoleFromInstanceProfile no borrada\n"},');
            else
                $this->logUser('{"five":"\nRoleFromInstanceProfile borrado\n"},');
    

            $res = $role->removeRoleFromInstanceProfile( CDH_IMAGE_removeRoleFromInstanceProfile );
            if( $res == false )
                $this->logUser('{"five":"\nRoleFromInstanceProfile no borrada\n"},');
            else
                $this->logUser('{"five":"\nRoleFromInstanceProfile borrado\n"},');

            


            $res = $role->delete( CDH_IMAGE_ROLE ['RoleName'] );
            if( $res == false )
                $this->logUser('{"five":"\nrole $roleId no borrada\n"},');
            else
                $this->logUser('{"five":"\nrole $roleId borrada\n"},');
            
            

            $res = $instanceProfile->delete( CDH_IMAGE_INSTANCE_PROFILE['InstanceProfileName'] );
            if( $res == false )
                $this->logUser('{"five":"\nL397 - instanceProfile no borrada\n"},');
            
            $res = $policy->delete( $policyArn );
                if( $res == false )
                    $this->logUser('{"five":"\npolicy $policyId no borrada\n"},');
                      
        
            $res = $securityGroup->delete( $securityGroupId );
            if( $res == false )
                $this->logUser('{"five":"\nsecurityGroup $securityGroupId no borrada\n"},');
                                  
            $this->logUser('{"five":"\nL313 - Deleting\n"},');       
          
            $this->logUser('{"five":"\nL165 - Everything Delete\n"},');   

            return true;

        }


    }





?>
