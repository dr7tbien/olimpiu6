<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsCloudator.php';

    require_once 'classes/CDH_awsSecurityGroup.php';
    require_once 'classes/CDH_awsKeyPair.php';
    require_once 'classes/CDH_awsPolicy.php';
    require_once 'classes/CDH_awsRole.php';


    require_once 'classes/CDH_awsSubnet.php'                ;
    require_once 'classes/CDH_awsTargetGroup.php'           ;
    require_once 'classes/CDH_awsListener.php'           ;
    
    require_once 'classes/CDH_awsLoadBalancer.php'          ;
    require_once 'classes/CDH_awsLaunchConfiguration.php'   ;
    require_once 'classes/CDH_awsAutoScalingGroup.php'      ;
    require_once 'classes/CDH_awsInstanceProfile.php'      ;
    require_once 'classes/CDH_awsImage.php'      ;
    

    require_once 'classes/CDH_awsListener.php';       

    require_once 'defines/services_aws/CDH_awsDefineStep0NetSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep3ImageSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep4LoadBalancingSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep5AutoScalingGroupFrontEndSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep6AutoScalingGroupBackEndSystem.php';



    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsStep6AutoScalingGroupBackEndSystem extends CDH_abstractAwsCloudator{
        
        private $clientCwt;
        #private $subnet0Name;
        #private $subnet1Name;
        #private $subnet2Name;
        

        public function __construct(){
            /*
            if ( touch( CDH_IMAGE_FILE_USER_DATA ) == false){
                $this->logUser('{"eight": "\nL035 No touched file. " . CDH_IMAGE_FILE_USER_DATA . "\n";
                die('\nL036 Problemas touching\n');
            }
            */

            $this->createNames();

        }

    
        public function createForCloudator() {
            /*
            $this->adminAreaTargetGroup443          ();
            $this->adminAreaRules443                ();
            $this->adminAreaKeyPair                 ();
            $policyArn  = $this->adminAreaIamPolicy ();
            $roleArn    = $this->adminAreaIamRole   ( $policyArn);
            $groupId = $this->adminAreaSecurityGroup           ();
            $this->adminAreaLaunchConfiguration     ( $groupId );
            $this->adminAreaAutoScalingGroup        ();
            $this->adminAreaAutoAlarmDown           ();
            $this->adminAreaAutoAlarmUp             ();
            */


            
            #region TargetGroup443
            $service = $this->createTargetGroup443();
            if( $service == false ) {
                $this->logAdmin("L078 - TargetGroup443 no creado\n");
                return false;
            }
            $this->logUser('{"eight": "\nL079 - TargetGroup443 creado\n"},');
            #endregion TargetGroup443
            
            #region createRules443
            $service = $this->createRule(); 
            if( $service == false ) {
                 $this->logAdmin("\nL085 - Rules443 no creado\n");
                 return false;
            }
            $this->logUser('{"eight": "\nL086 - Rules443 creado\n"},');            
            #endregion createRules443
                        
            #region createKeyPair
            $service = $this->createKeyPair(); 
            if( $service == false ) {
                 $this->logAdmin("\nL092 - KeyPair no creado\n");
                 return false;
            }
            $this->logUser('{"eight": "\nL092 - KeyPair creado\n"},');
            #endregion createKeyPair
            
            
            #region createPolicy
            $service = $this->createPolicy(); 
            if( $service == false ) {
                 $this->logAdmin("\nL099 - Policy no creado\n");
                 return false;
            }
            $this->logUser('{"eight": "\nL100 - Policy creado\n"},');            
            #endregion createPolicy
            
           
            #region role
            $service = $this->createRole(); 
            if( $service == false ){              
                 $this->logAdmin("\nL106 - Role no creado\n");
                 return false;
            }
            $this->logUser('{"eight": "\nL107 - Role creado\n"},');                       
            #endregion role
            
            
            #region SecurityGroup
            $service = $this->createSecurityGroup(); 
            if( $service == false ){               
                 $this->logAdmin("\nL113 - SecurityGroup no creado\n");
                 return false;
            }
            $this->logUser('{"eight": "\nL114 - SecurityGroup creado\n"},');                        
            #endregion SecurityGroup
            

            #region createLaunchConfiguration
            $service = $this->createLaunchConfiguration(); 
            if( $service == false ) {
                 $this->logAdmin("\nL120 - LaunchConfiguration Service no creado\n");
                 return false;
            }
            $this->logUser('{"eight": "\nL121 - LaunchConfiguration Service creado\n"},');            
            #endregion createLaunchConfiguration
            
                        
            #region createAutoscalingGroup
            $service = $this->createAutoscalingGroup(); 
            if( $service == false ) {
                 $this->logAdmin("\nL127 - AutoscalingGroup Service no creado\n");
                 return false;
            }
            $this->logUser('{"eight": "\nL128 - AutoscalingGroup Service creado\n"},');            
            #endregion AutoscalingGroup
            
                        
            #region createAutoscalingGroupAlarmUp
            $service = $this->autoscalingGroupAlarmUp(); 
            if( $service == false ) {
                 $this->logAdmin("\nL134 - AutoscalingGroupAlarmUp Service no creado\n");
                 return false;
            }
            $this->logUser('{"eight": "\nL135 - AutoscalingGroupAlarmUp Service creado\n"},');            
            #endregion AutoscalingGroupAlarmUp
            

            #region createAutoscalingGroupAlarmDown
            $service = $this->autoscalingGroupAlarmDown(); 
            if( $service == false ) {
                 $this->logAdmin("\nL141 - AutoscalingGroupAlarmDown Service no creado\n");
                 return false;
            }
            $this->logUser('{"eight": "\nL142 - AutoscalingGroupAlarmDown Service creado\n"},');            
            #endregion createAutoscalingGroupAlarmDown
            
            $this->logUser('{"eight": "\nL156 - TODO AutoscalingGroup creado\n"},');

            return true;
                        
        }


        public function createTargetGroup443(){
            $service        = new CDH_awsTargetGroup();
            $vpc            = new CDH_awsVpc();
            
            $data           = CDH_AUTOSCALING_BACK_END_createTargetGroup443;
            $data['VpcId']  = $vpc->getIdByName( $this->getVpcName() );
            
            $arnTargetGroup        = $service->createWithModifyTargetGroupAttributes( $data );
            if( $arnTargetGroup == false){
                $this->logAdmin("L161 - TG443 no creado\n");
            }

            $this->logUser('{"eight": "\nL163 - TargetGroup 443 creado\n"},');
            return $arnTargetGroup;
    
        }

        public function createRule(){
            $targetGroup    = new CDH_awsTargetGroup();
            $listener       = new CDH_awsListener();
            $loadBalancer   = new CDH_awsLoadBalancer();
            


            $arnTargetGroup = $targetGroup->getArnByName( CDH_AUTOSCALING_BACK_END_createTargetGroup443['Name'] );

            $arnLoadBalancer = $loadBalancer->getArnByName(CDH_LOAD_BALANCING_LOAD_BALANCER['Name']);


            $arnListener    = $listener->getArnByLoadBalancerName( $arnLoadBalancer, 443);
            #$this->logUser('{"eight": "\nL180 "; die();
            

            $CDH_AUTOSCALING_BACK_END_createRule = CDH_AUTOSCALING_BACK_END_createRule;

            $CDH_AUTOSCALING_BACK_END_createRule['Actions'][0]['TargetGroupArn']    = $arnTargetGroup;
            $CDH_AUTOSCALING_BACK_END_createRule['ListenerArn']                     = $arnListener;

            #print_r( $CDH_AUTOSCALING_BACK_END_createRule ); die( "\nL182\n" );
            $result         = $targetGroup->createRule( $CDH_AUTOSCALING_BACK_END_createRule );

            
            return true;


        }
        
        public function createKeyPair(){
            $service        = new CDH_awsKeyPair();
            $service        = $service->create( CDH_AUTOSCALING_BACK_END_createKeyPair, CDH_AUTOSCALING_BACK_END_KEYPAIR_sshAccessFilePath );
            #return true;
            if( $service == false){
                $this->logUser('{"eight": "\nL201 - KEYPAIR no creado\n"},');
                return false;
            }

            return true;
        }

        public function createPolicy(){
            $service        = new CDH_awsPolicy();
            $data           = CDH_AUTOSCALING_BACK_END_createPolicy;
            #return true;
            $service        = $service->create( $data );
            if( $service == false){
                $this->logUser('{"eight": "\nL175 - Policy ASG no creado\n"},');
                return false;
            }            
            return true;
        }

        public function createRole(){
            #1. Create Role
            #2. Create InstanceProfile
            #3. Add Role to Instance Profile
            #4. Attach Role Policy 

            $role               = new CDH_awsRole();
            $policy             = new CDH_awsPolicy();
            $instanceProfile    = new CDH_awsInstanceProfile();

            $dataAddRoleToInstanceProfile = [
                'InstanceProfileName'   => CDH_AUTOSCALING_BACK_END_createRole['RoleName'], // REQUIRED
                'RoleName'              => CDH_AUTOSCALING_BACK_END_createRole['RoleName'], // REQUIRED
            ];

            $dataAttachRolePolicy = [
                   'PolicyArn' => $policy->getArnByName( CDH_AUTOSCALING_BACK_END_createPolicy['PolicyName'] ), // REQUIRED
                   'RoleName' => CDH_AUTOSCALING_BACK_END_createRole['RoleName'], // REQUIRED
            ]; 

            #print_r($dataAddRoleToInstanceProfile);
            #print_r($dataAttachRolePolicy);
            #die("\nL242 \n");

            $resultRole             = $role->create                     ( CDH_AUTOSCALING_BACK_END_createRole );
            sleep(2);
            $resultInstanceProfile  = $instanceProfile->create          ( CDH_AUTOSCALING_BACK_END_createInstanceProfile );
            sleep(10);
            $resultAddRole          = $role->addRoleToInstanceProfile   ( $dataAddRoleToInstanceProfile );
            sleep(2);
            $resultAttachRolePolicy = $role->attachRolePolicy( $dataAttachRolePolicy );

            
            return true;
        }

        public function createSecurityGroup(){

            $vpc            = new CDH_awsVpc();
            $securityGroup  = new CDH_awsSecurityGroup();
            
            $data       = CDH_AUTOSCALING_BACK_END_createSecurityGroup;
            $dataRules  = CDH_AUTOSCALING_BACK_END_authorizeSecurityGroupIngress;

            $data['VpcId']  = $vpc->getIdByName( $this->getVpcName() );
            
            $result = $securityGroup->create( $data );

            $dataRules['GroupId'] = $result['GroupId'];

            $result = $securityGroup->authorizeSecurityGroupIngress( $dataRules );
        
            return true;            
        }

        /*
        private function createInstanceProfile(){
            $service        = new CDH_awsInstanceProfile();
            $policy         = new CDH_awsPolicy();
            $clientIam      = $service->getClientIam();


            $arnPolicy      = $policy->getArnByName( CDH_AUTOSCALING_FRONT_END_policy['PolicyName'] );

            if( $arnPolicy == false ){
                $this->logUser('{"eight": "\nL209 no se puede ejecutar getArnByName \n";
                die();
            }

            $data           = CDH_AUTOSCALING_FRONT_END_instanceProfile;
            
            #return true;
            $service        = $service->create( $data );
            if( $service == false){
                $this->logUser('{"eight": "\nL187 - Role no creado\n";
                return false;
            }
                                       
            
            try{
                $result = $clientIam->addRoleToInstanceProfile([
                    'InstanceProfileName'   => CDH_AUTOSCALING_FRONT_END_role['RoleName'], // REQUIRED
                    'RoleName'              => CDH_AUTOSCALING_FRONT_END_role['RoleName'], // REQUIRED
                ]);

            }catch (AwsException $e) {
                $this->logUser('{"eight": $e->getMessage();
                die( "\nL225 -No se ha podido con addRoleToInstanceProfile \n" );
            }


            try{
                $result = $clientIam->attachRolePolicy([
                    'PolicyArn' => $arnPolicy, // REQUIRED
                    'RoleName'  => CDH_AUTOSCALING_FRONT_END_role['RoleName'], // REQUIRED
                ]);

            }catch (AwsException $e) {
                $this->logUser('{"eight": $e->getMessage();
                die( "\nL247 - No se ha podido con attachRolePolicy \n" );
            }

            return true;

        }
        */

        public function createLaunchConfiguration(){
            $service            = new CDH_awsLaunchConfiguration();
            $instanceProfile    = new CDH_awsInstanceProfile();
            $securityGroup      = new CDH_awsSecurityGroup();
            $image              = new CDH_awsImage();

            $instanceProfileArn = $instanceProfile->getArnByName( CDH_AUTOSCALING_BACK_END_createRole           ['RoleName'] );
            $securityGroupId    = $securityGroup->getIdByName   ( CDH_AUTOSCALING_BACK_END_createSecurityGroup  ['GroupName']);
            $imageId            = $image->getIdByName( CDH_IMAGE['Name'] );
            
            if( $instanceProfileArn == false ) {
                $this->logAdmin("\n\nL198 - Error rescatando el arn del Instance Profile\n\n");
                return false;
            }

            if( $imageId == false ){
                 $this->logAdmin("L202 - Error rescatando el id de la imagen\n");
                 return false;
            }

            $CDH_AUTOSCALING_launchConfiguration                        = CDH_AUTOSCALING_BACK_END_launchConfiguration;
            $CDH_AUTOSCALING_launchConfiguration['IamInstanceProfile']  = $instanceProfileArn;
            $CDH_AUTOSCALING_launchConfiguration['ImageId']             = $imageId;
            $CDH_AUTOSCALING_launchConfiguration['SecurityGroups'][0]   = $securityGroupId;

            #print_r ( $CDH_AUTOSCALING_launchConfiguration ); die("\n  L363 \n");

            $service        = $service->create( $CDH_AUTOSCALING_launchConfiguration );
            
                
            return true;

        }

        public function createAutoscalingGroup(){
            $service        = new CDH_awsAutoScalingGroup();
            $targetGroup    = new CDH_awsTargetGroup();
            $subnet         = new CDH_awsSubnet(); 


            $CDH_AUTOSCALING_autoScalingGroup = CDH_AUTOSCALING_BACK_END_autoScalingGroup;

            $targetGroupArn080  = $targetGroup->getArnByName( CDH_LOAD_BALANCING_TARGET_GROUP_080['Name'] );
            $targetGroupArn443  = $targetGroup->getArnByName( CDH_LOAD_BALANCING_TARGET_GROUP_443['Name'] );
            $subnetIdPrivate0   = $subnet->getIdByName      ( CDH_NET_SYSTEM_SUBNETS[3]['TagSpecifications'][0]['Tags'][0]['Value'] );
            $subnetIdPrivate1   = $subnet->getIdByName      ( CDH_NET_SYSTEM_SUBNETS[4]['TagSpecifications'][0]['Tags'][0]['Value'] );


            $CDH_AUTOSCALING_autoScalingGroup['TargetGroupARNs'][0]   = $targetGroupArn080;
            $CDH_AUTOSCALING_autoScalingGroup['TargetGroupARNs'][1]   = $targetGroupArn443;
            $CDH_AUTOSCALING_autoScalingGroup['VPCZoneIdentifier']    = "$subnetIdPrivate0, $subnetIdPrivate1";
            
            #print_r( $CDH_AUTOSCALING_FRONT_END_autoScalingGroup );

            $result        = $service->create( $CDH_AUTOSCALING_autoScalingGroup );

            return true;
            
        }

        public function autoscalingGroupAlarmUp(){
            $autoScalingGroup   = new CDH_awsAutoScalingGroup();
            $policy             = new CDH_awsPolicy();
            
            
            $data_0 = CDH_AUTOSCALING_BACK_END_autoScalingGroup_alarmUp_putScalingPolicy;
            $data_1 = CDH_AUTOSCALING_BACK_END_autoScalingGroup_alarmUp_putMetricAlarm;

            $result = $autoScalingGroup->putScalingPolicy( $data_0 ); 

            $data_1['AlarmActions'][0]  = $result['PolicyARN'];

            if( $autoScalingGroup->putMetricAlarm( $data_1 ) == false ){
                 $this->logAdmin("\nL394 - Error en  putMetricAlarm \n");
                 return false;
            }

            return true;
            
        }

        public function autoscalingGroupAlarmDown(){

            $autoScalingGroup   = new CDH_awsAutoScalingGroup();
            $policy             = new CDH_awsPolicy();
                        
            $data_0 = CDH_AUTOSCALING_BACK_END_autoScalingGroup_alarmDown_putScalingPolicy;
            $data_1 = CDH_AUTOSCALING_BACK_END_autoScalingGroup_alarmDown_putMetricAlarm;

            $result = $autoScalingGroup->putScalingPolicy( $data_0 ); 

            $data_1['AlarmActions'][0]  = $result['PolicyARN'];

            if( $autoScalingGroup->putMetricAlarm( $data_1 ) == false ){
                 $this->logAdmin("\nL413 - Error en  putMetricAlarm \n");
                 return false;
            }

            return true;

   
        }


    }





?>
