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
    require_once 'classes/CDH_awsLoadBalancer.php'          ;
    require_once 'classes/CDH_awsLaunchConfiguration.php'   ;
    require_once 'classes/CDH_awsAutoScalingGroup.php'      ;
    require_once 'classes/CDH_awsInstanceProfile.php'      ;
    require_once 'classes/CDH_awsImage.php'                 ;

    require_once 'classes/CDH_awsListener.php';   


    require_once 'defines/services_aws/CDH_awsDefineStep0NetSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep3ImageSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep4LoadBalancingSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep5AutoScalingGroupFrontEndSystem.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsStep5AutoScalingGroupFrontEndSystem extends CDH_abstractAwsCloudator{
        
        private $clientCwt;
        #private $subnet0Name;
        #private $subnet1Name;
        #private $subnet2Name;
        

        public function __construct(){
            /*
            if ( touch( CDH_IMAGE_FILE_USER_DATA ) == false){
                $this->logUser('{"seven": "\nL035 No touched file. " . CDH_IMAGE_FILE_USER_DATA . "\n";
                die('\nL036 Problemas touching\n');
            }
            */

            $this->createNames();

        }

    
        public function createForCloudator() {
            
            #region Sg
            $service = $this->createSecurityGroup();
            if( $service == false ) {
                $this->logAdmin("L047 - Security Group no creado\n");
                return false;
            }
            $this->logUser('{"seven": "\nL059 - Security Group creado\n"},');
            #endregion Sg
             
            #region keyPair
            $service = $this->createKeyPair(); 
            if( $service == false ) {
                $this->logAdmin("\nL056 - KeyPair no creado\n");
                return false;
            }
            $this->logUser('{"seven": "\nL068 - KeyPair creado\n"},');            
            #endregion keyPair
            
            
            #region IamPolicy
            $service = $this->createPolicy(); 
            if( $service == false ) {
                $this->logAdmin("\nL065 - IamPolicy no creado\n");
            }
            $this->logUser('{"seven": "\nL068 - IamPolicy creado\n"},');
            #endregion IamPolicy
            
            #region createIamRole
            $service = $this->createRole(); 
            if( $service == false ) {
                $this->logAdmin("\nL074 - IamRole no creado\n");
                return false;
            }
            $this->logUser('{"seven": "\nL077 - IamRole creado\n"},');            
            #endregion createIamRole
           

            #region instanceProfile
            $service = $this->createInstanceProfile(); 
            if( $service == false ){            
                $this->logAdmin("\nL087 - InstanceProfile no creado\n");
                return false;
            }
            $this->logUser('{"seven": "\nL088 - InstanceProfile creado\n"},');                       
            #endregion instanceProfile
            

            #region createLaunchConfiguration
            $service = $this->createLaunchConfiguration(); 
            if( $service == false ) {
                $this->logUser('{"seven": "\nL084 - LaunchConfiguration Service no creado\n"},');
                return false;
            }
            $this->logUser('{"seven": "\nL087 - LaunchConfiguration Service creado\n"},');           
            #endregion createLaunchConfiguration
            
            
            
            #region createAutoscalingGroup
            $service = $this->createAutoscalingGroup(); 
            if( $service == false ) {
                $this->logUser('{"seven": "\nL093 - AutoscalingGroup Service no creado\n"},');
                return false;
            }
            $this->logUser('{"seven": "\nL096 - AutoscalingGroup Service creado\n"},');           
            #endregion AutoscalingGroup
            
            
            #region createAutoscalingGroupAlarmUp
            $service = $this->autoscalingGroupAlarmUp(); 
            if( $service == false ) {
                $this->logUser('{"seven": "\nL103 - AutoscalingGroupAlarmUp Service no creado\n"},');
                return false;
            }
            $this->logUser('{"seven": "\nL106 - AutoscalingGroupAlarmUp Service creado\n"},');            
            #endregion AutoscalingGroupAlarmUp

            #region createAutoscalingGroupAlarmDown
            $service = $this->autoscalingGroupAlarmDown(); 
            if( $service == false ) {
                $this->logUser('{"seven": "\nL112 - AutoscalingGroupAlarmDown Service no creado\n"},');
                return false;
            }
            $this->logUser('{"seven": "\nL115 - AutoscalingGroupAlarmDown Service creado\n"},');            
            #endregion createAutoscalingGroupAlarmDown

            $this->logUser('{"seven": "\nL118 - TODO AutoscalingGroup creado\n"},');

            return true;
                        
        }


        public function createSecurityGroup(){
            $vpc        = new CDH_awsVpc();
            $service    = new CDH_awsSecurityGroup();
            $vpc        = new CDH_awsVpc();

            $data       = CDH_AUTOSCALING_FRONT_END_createSecurityGroup;
            $dataRules  = CDH_AUTOSCALING_FRONT_END_createRulesIngress;

            $data['VpcId']  = $vpc->getIdByName( $this->getVpcName() );
            
            $result = $service->create( $data );


            if( $result == false ){
                $this->logAdmin( "\nL150 - SG no creadas\n" );
                return false;
            }

            $dataRules['GroupId'] = $result['GroupId'];

            $result = $service->authorizeSecurityGroupIngress( $dataRules );
    
            if( $result == false){
                $this->logUser('{"seven": "\nL147 - Rules para SG no creadas\n"},');
                return false;
            }
    
            return true;            
        }

        public function createKeyPair(){
            $service        = new CDH_awsKeyPair();
            $service        = $service->create( CDH_AUTOSCALING_FRONT_END_keyPair, CDH_AUTOSCALING_FRONT_END_KEYPAIR_sshAccessFilePath );
            #return true;
            if( $service == false){
                $this->logUser('{"seven": "\nL169 - KEYPAIR no creado\n"},');
                return false;
            }

            return true;
        }

        public function createPolicy(){
            $service        = new CDH_awsPolicy();
            $data           = CDH_AUTOSCALING_FRONT_END_policy;
            #return true;
            $service        = $service->create( $data );
            if( $service == false){
                $this->logUser('{"seven": "\nL175 - Policy ASG no creado\n"},');
                return false;
            }            
            return true;
        }

        public function createRole(){
            $service        = new CDH_awsRole();
            $data           = CDH_AUTOSCALING_FRONT_END_role;
            #return true;
            $service        = $service->create( $data );
            if( $service == false){
                $this->logUser('{"seven": "\nL187 - Role no creado\n"},');
                return false;
            }
            
            return true;
        }

        private function createInstanceProfile(){
            $service        = new CDH_awsInstanceProfile();
            $policy         = new CDH_awsPolicy();
            $clientIam      = $service->getClientIam();


            $arnPolicy      = $policy->getArnByName( CDH_AUTOSCALING_FRONT_END_policy['PolicyName'] );

            if( $arnPolicy == false ){
                $this->logUser('{"seven": "\nL209 no se puede ejecutar getArnByName \n"},');
                return false;
            }

            $data           = CDH_AUTOSCALING_FRONT_END_instanceProfile;
            
            #return true;
            $service        = $service->create( $data );
            if( $service == false){
                $this->logUser('{"seven": "\nL187 - Role no creado\n"},');
                return false;
            }
                                       
            
            try{
                $result = $clientIam->addRoleToInstanceProfile([
                    'InstanceProfileName'   => CDH_AUTOSCALING_FRONT_END_role['RoleName'], // REQUIRED
                    'RoleName'              => CDH_AUTOSCALING_FRONT_END_role['RoleName'], // REQUIRED
                ]);

            }catch (AwsException $e) {
                //$this->logUser('{"seven": $e->getMessage();
                //die( "\nL225 -No se ha podido con addRoleToInstanceProfile \n" );
                return false;
            }


            try{
                $result = $clientIam->attachRolePolicy([
                    'PolicyArn' => $arnPolicy, // REQUIRED
                    'RoleName'  => CDH_AUTOSCALING_FRONT_END_role['RoleName'], // REQUIRED
                ]);

            }catch (AwsException $e) {
                //$this->logUser('{"seven": $e->getMessage();
                //die( "\nL247 - No se ha podido con attachRolePolicy \n" );
                return false;
                
            }
            sleep( 10 );
            return true;

        }


        public function createLaunchConfiguration(){
            $service            = new CDH_awsLaunchConfiguration();
            $instanceProfile    = new CDH_awsInstanceProfile();
            $securityGroup      = new CDH_awsSecurityGroup();
            $image              = new CDH_awsImage();

            $instanceProfileArn = $instanceProfile->getArnByName( CDH_AUTOSCALING_FRONT_END_role                ['RoleName'] );
            $securityGroupId    = $securityGroup->getIdByName   ( CDH_AUTOSCALING_FRONT_END_createSecurityGroup ['GroupName']);
            
            if( $instanceProfileArn == false ) {
                $this->logAdmin("\n\nL198 - Error rescatando el arn del Instance Profile\n\n");
                return false;
            }

            $imageId = $image->getIdByName( CDH_IMAGE['Name'] );
            if( $imageId == false ) {
                $this->logAdmin("\n\nL202 - Error rescatando el id de la imagen\n\n");
                return false;
            }

            $CDH_AUTOSCALING_launchConfiguration                        = CDH_AUTOSCALING_FRONT_END_launchConfiguration;
            $CDH_AUTOSCALING_launchConfiguration['IamInstanceProfile']  = $instanceProfileArn;
            $CDH_AUTOSCALING_launchConfiguration['ImageId']             = $imageId;
            $CDH_AUTOSCALING_launchConfiguration['SecurityGroups'][0]   = $securityGroupId;

            #print_r ( $CDH_AUTOSCALING_launchConfiguration );

            $service        = $service->create( $CDH_AUTOSCALING_launchConfiguration );
            if( $service == false){
                 $this->logAdmin( "\nL187 - LaunchConfiguration no creado\n" );
                return false;
            }
                
            return true;

        }

        public function createAutoscalingGroup(){
            $service        = new CDH_awsAutoScalingGroup();
            $targetGroup    = new CDH_awsTargetGroup();
            $subnet         = new CDH_awsSubnet(); 


            $CDH_AUTOSCALING_FRONT_END_autoScalingGroup = CDH_AUTOSCALING_FRONT_END_autoScalingGroup;

            $targetGroupArn080   = $targetGroup->getArnByName( CDH_LOAD_BALANCING_TARGET_GROUP_080['Name'] );
            $targetGroupArn443  = $targetGroup->getArnByName( CDH_LOAD_BALANCING_TARGET_GROUP_443['Name'] );
            $subnetIdPrivate0   = $subnet->getIdByName( CDH_NET_SYSTEM_SUBNETS[3]['TagSpecifications'][0]['Tags'][0]['Value'] );
            $subnetIdPrivate1   = $subnet->getIdByName( CDH_NET_SYSTEM_SUBNETS[4]['TagSpecifications'][0]['Tags'][0]['Value'] );


            $CDH_AUTOSCALING_FRONT_END_autoScalingGroup['TargetGroupARNs'][0]   = $targetGroupArn080;
            $CDH_AUTOSCALING_FRONT_END_autoScalingGroup['TargetGroupARNs'][1]   = $targetGroupArn443;
            $CDH_AUTOSCALING_FRONT_END_autoScalingGroup['VPCZoneIdentifier']    = "$subnetIdPrivate0, $subnetIdPrivate1";
            
            #print_r( $CDH_AUTOSCALING_FRONT_END_autoScalingGroup );

            $result        = $service->create( $CDH_AUTOSCALING_FRONT_END_autoScalingGroup );
            if( $result == false){
                $this->logAdmin( "\nL314 - LaunchConfiguration no creado\n" );
                return false;
            }
                
            return true;


            return true;
            
        }

        public function autoscalingGroupAlarmUp(){
            $autoScalingGroup   = new CDH_awsAutoScalingGroup();
            $policy             = new CDH_awsPolicy();
            
            
            $data_0 = CDH_AUTOSCALING_FRONT_END_autoScalingGroup_alarmUp_putScalingPolicy;
            $data_1 = CDH_AUTOSCALING_FRONT_END_autoScalingGroup_alarmUp_putMetricAlarm;

            $result = $autoScalingGroup->putScalingPolicy( $data_0 ); 

            if( $result == false ){
                 $this->logAdmin("\nL337 - Error en  putScalingPolicy \n");
                 return false;
            }

            $data_1['AlarmActions'][0]  = $result['PolicyARN'];

            if( $autoScalingGroup->putMetricAlarm( $data_1 ) == false ){
                 $this->logAdmin("\nL338 - Error en  putMetricAlarm \n");
                 return false;
            }

            return true;
            
        }

        public function autoscalingGroupAlarmDown(){

            $autoScalingGroup   = new CDH_awsAutoScalingGroup();
            $policy             = new CDH_awsPolicy();
                        
            $data_0 = CDH_AUTOSCALING_FRONT_END_autoScalingGroup_alarmDown_putScalingPolicy;
            $data_1 = CDH_AUTOSCALING_FRONT_END_autoScalingGroup_alarmDown_putMetricAlarm;

            $result = $autoScalingGroup->putScalingPolicy( $data_0 ); 

            if( $result == false ){
                 $this->logAdmin("\nL337 - Error en  putScalingPolicy \n");
                 return false;
            }

            $data_1['AlarmActions'][0]  = $result['PolicyARN'];

            if( $autoScalingGroup->putMetricAlarm( $data_1 ) == false ){
                 $this->logAdmin("\nL338 - Error en  putMetricAlarm \n");
                return false;
            }

            return true;
   
        }


    }





?>
