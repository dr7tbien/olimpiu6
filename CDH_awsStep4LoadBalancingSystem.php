<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsCloudator.php';

    require_once 'classes/CDH_awsSecurityGroup.php';
    require_once 'classes/CDH_awsSubnet.php';
    require_once 'classes/CDH_awsTargetGroup.php';
    require_once 'classes/CDH_awsLoadBalancer.php';
    require_once 'classes/CDH_awsListener.php';

    require_once 'classes/CDH_awsRequestCertificate.php';
    require_once 'classes/CDH_awsHostedZone.php';
    require_once 'classes/CDH_awsRecordSet.php';

    #require_once 'defines/services_aws/CDH_awsDefineStep00RecordSetSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep4LoadBalancingSystem.php';


    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsStep4LoadBalancingSystem extends CDH_abstractAwsCloudator{
        
        
        #private $subnet0Name;
        #private $subnet1Name;
        #private $subnet2Name;
        

        public function __construct(){
            /*
            if ( touch( CDH_IMAGE_FILE_USER_DATA ) == false){
                $this->logUser('{"six": "\nL035 No touched file. " . CDH_IMAGE_FILE_USER_DATA . "\n";
                die('\nL036 Problemas touching\n');
            }
            */

            $this->createNames();

        }

    
        public function createForCloudator() {
            
            #region RequestCertificates
            if( $this->createRequestCertificates() == false ){
                 $this->logUser('{"six": "\nL046 - alguno o todos los RequestCertificates NO HAN sido creados\n"},');
                return false;
            }
            $this->logUser('{"six": "\nL049 - RequestCertificates creados\n"},');
            #endregion RequestCertificates
            
            #region Sg
            $service = $this->createSecurityGroup();
            if( $service == false ) {
                $this->logUser('{"six": "\nL045 - Security Group no creado\n"},');
                return false;
            }
            $this->logUser('{"six": "\nL054 - Security Group creado\n"},');
            #endregion Sg
                
            #region TargetGroup080
            $service = $this->createTargetGroup080(); 
            if( $service == false ) {
                $this->logUser('{"six": "\nL060 - TargetGroup080 no creado\n"},');
                return false;
            }
            $this->logUser('{"six": "\nL063 - TargetGroup080 creado\n"},');            
            #endregion TargetGroup080
            
            #region TargetGroup443
            $service = $this->createTargetGroup443(); 
            if( $service == false ) {
                $this->logUser('{"six": "\nL069 - TargetGroup443 no creado\n"},')
                return false;
            }
            $this->logUser('{"six": "\nL072 - TargetGroup443 creado\n"},');
            #endregion TargetGroup443
            
            #region createLoadBalancer
            $service = $this->createLoadBalancer(); 
            if( $service == false ) {
                $this->logUser('{"six": "\nL078 - LoadBalancer no creado\n"},');
                return false;
            }
            $this->logUser('{"six": "\nL081 - LoadBalancer creado\n"},');           
            #endregion createLoadBalancer
            
            
            #region createListener080
            $service = $this->createListener080(); 
            if( $service == false ) {
                $this->logUser('{"six": "\nL106 - Listener080 Service no creado\n"},');
                return false;
            }
            $this->logUser('{"six": "\nL109 - Listener080 Service creado\n"},');        
            #endregion createListener080
            

            #region createListener443
            $service = $this->createListener443(); 
            if( $service == false ) {
                $this->logUser('{"six": "\nL106 - Listener443 Service no creado\n"},');
                return false;
            }
            $this->logUser('{"six": "\nL109 - Listener443 Service creado\n"},');         
            #endregion createListener443

            $this->logUser('{"six": "\nL099 - TODO Load Balancing creado\n"},');

            return true;
                        
        }

        public function createRequestCertificates(){
            $requestCertificate = new CDH_awsRequestCertificate();
            //var_export(CDH_LOAD_BALANCING_REQUEST_CERTIFICATE);
            //die("\n125"); 
            $arnUECertificate     = $requestCertificate->create( CDH_LOAD_BALANCING_REQUEST_CERTIFICATE ); 
            if ( $arnUECertificate == false ){
                $this->logAdmin("\n130 ** arnUECertificate no creado");
                return false;
            }
            $resultado = $requestCertificate->createRecordSetForRequestCertificate( $arnUECertificate, CDH_HOSTED_ZONE['Name'] );

            if( $resultado == false ){
                $this->logUser('{"six": "\nL142 - Error creando recordset\n"},');
                return false;
            }
            //$this->logUser('{"six": "\n136";
            /*if( $requestCertificate->waitToBeIssued($arnUECertificate) == false ) {
                $this->logUser('{"six": "\nL139 - Error creando certificado \n";
                return false;
            }*/
            //$this->logUser('{"six": "\n141";


            $region = CDH_AWS_REGION_VIRGINIA;
            $requestCertificate->setClientAcm( $region );
            //$this->logUser('{"six": "\n146";
            $arnEUCertificate = $requestCertificate->create( CDH_LOAD_BALANCING_REQUEST_CERTIFICATE );

            if ( $arnEUCertificate == false )
                return false;

            //$this->logUser('{"six": "\n152";
            if( $requestCertificate->waitToBeIssued($arnEUCertificate) == false ) {
                $this->logUser('{"six": "\nL156 - Error creando certificado \n"},');
                return false;
            }
            //$this->logUser('{"six": "\n157";
            return true;
            

        }


        public function createSecurityGroup(){
            $service        = new CDH_awsSecurityGroup();
            $vpc            = new CDH_awsVpc();
            $data           = CDH_LOAD_BALANCING_SECURITY_GROUP;
            $data['VpcId']  = $vpc->getIdByName( $this->getVpcName() );
            #return true;
            $service            = $service->create( $data );
            if( $service == false){
                $this->logUser('{"six": "\nL112 - SG no creado\n"},');
                return false;
            }

            return true;     
        }



        public function createTargetGroup080(){
            $service        = new CDH_awsTargetGroup();
            $vpc            = new CDH_awsVpc();
            
            $data = CDH_LOAD_BALANCING_TARGET_GROUP_080;
            $data['VpcId']  = $vpc->getIdByName( $this->getVpcName() );
            
            $service            = $service->create( $data );
            if( $service == false){
                $this->logUser('{"six": "\nL130 - TG80 no creado\n"},');
                return false;
            }

            return true;
        }


        public function createTargetGroup443(){
            $service        = new CDH_awsTargetGroup();
            $vpc            = new CDH_awsVpc();
            
            $data           = CDH_LOAD_BALANCING_TARGET_GROUP_443;
            $data['VpcId']  = $vpc->getIdByName( $this->getVpcName() );
            
            $service        = $service->create( $data );
            if( $service == false){
                $this->logUser('{"six": "\nL149 - TG443 no creado\n"},');
                return false;
            }

            return true;
        }

        public function createLoadBalancer(){
            $service        = new CDH_awsLoadBalancer();
            #$vpc            = new CDH_awsVpc();
            $subnet         = new CDH_awsSubnet();
            $securityGroup  = new CDH_awsSecurityGroup();
            
            
            $data                       = CDH_LOAD_BALANCING_LOAD_BALANCER;
            $data['SecurityGroups'] []  = $securityGroup->getIdByName   ( CDH_LOAD_BALANCING_SECURITY_GROUP['GroupName'] );
            $data['Subnets']        []  = $subnet->getIdByName          ( $this->getSubnetPublic0Name() );
            $data['Subnets']        []  = $subnet->getIdByName          ( $this->getSubnetPublic1Name() );

            $service                    = $service->create( $data );
            if( $service == false){
                $this->logUser('{"six": "\nL169 - ALB no creado\n"},');
                return false;
            }

            return true;
        }

        public function createListener080(){
            $service        = new CDH_awsListener();
            $targetGroup    = new CDH_awsTargetGroup();
            $loadBalancer   = new CDH_awsLoadBalancer();

            $targetGroupArn     = $targetGroup->getArnByName    ( CDH_LOAD_BALANCING_TARGET_GROUP_080 ['Name'] );
            $loadBalancerArn    = $loadBalancer->getArnByName   ( CDH_LOAD_BALANCING_LOAD_BALANCER    ['Name'] );

            $data           = CDH_LOAD_BALANCING_LISTENER_080; 

            $data['DefaultActions'][0]['ForwardConfig']['TargetGroups'][0]['TargetGroupArn']    = $targetGroupArn;
            $data['DefaultActions'][0]['TargetGroupArn']                                        = $targetGroupArn;
            $data['LoadBalancerArn']                                                            = $loadBalancerArn;

            #$data['TargetGroupArn'] = $targetGroup->getArnByName( CDH_LOAD_BALANCING_TARGET_GROUP_080['Name'] );

            $service = $service->create( $data );
            if( $service == false){
                $this->logUser('{"six": "\nL195 - Listener 80 no creado\n"},');
                return false;
            }

            return true;
        }

        public function createListener443(){
            $service            = new CDH_awsListener();
            $targetGroup        = new CDH_awsTargetGroup();
            $loadBalancer       = new CDH_awsLoadBalancer();
            $requestCertificate = new CDH_awsRequestCertificate();

            $arnCertificate     = $requestCertificate-> getArnByDnsName( CDH_DOMINIO_CON_PUNTO );
            $arnTargetGroup     = $targetGroup->getArnByName    ( CDH_LOAD_BALANCING_TARGET_GROUP_443 ['Name'] );
            $arnLoadBalancer    = $loadBalancer->getArnByName   ( CDH_LOAD_BALANCING_LOAD_BALANCER    ['Name'] );

            $data               = CDH_LOAD_BALANCING_LISTENER_443; 


            $data['Certificates'][0]['CertificateArn']                                          = $arnCertificate;
            $data['DefaultActions'][0]['ForwardConfig']['TargetGroups'][0]['TargetGroupArn']    = $arnTargetGroup;
            $data['DefaultActions'][0]['TargetGroupArn']                                        = $arnTargetGroup;
            $data['LoadBalancerArn']                                                            = $arnLoadBalancer;
            $data['TargetGroupArn'] = $targetGroup->getArnByName( CDH_LOAD_BALANCING_TARGET_GROUP_443['Name'] );

            //print_r( $data );

            #die( "\n\nL282\n\n" );


            $service = $service->create( $data );
            if( $service == false){
                $this->logUser('{"six": "\nL195 - Listener 80 no creado\n"},');
                return false;
            }

            return true;
            
        }


    }





?>
