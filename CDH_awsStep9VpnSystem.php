<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsCloudator.php';


    require_once 'classes/CDH_awsEfs.php';
    require_once 'classes/CDH_awsSecurityGroup.php';
    require_once 'classes/CDH_awsKeyPair.php';
    require_once 'classes/CDH_awsPolicy.php';
    require_once 'classes/CDH_awsRole.php';
    require_once 'classes/CDH_awsElasticIp.php';


    require_once 'classes/CDH_awsSubnet.php'                ;
    require_once 'classes/CDH_awsTargetGroup.php'           ;
    require_once 'classes/CDH_awsListener.php'           ;
    
    require_once 'classes/CDH_awsLoadBalancer.php'          ;
    require_once 'classes/CDH_awsLaunchConfiguration.php'   ;
    require_once 'classes/CDH_awsAutoScalingGroup.php'      ;

    require_once 'classes/CDH_awsInstanceProfile.php'      ;
    require_once 'classes/CDH_awsInstance.php'      ;
    require_once 'classes/CDH_awsImage.php'      ;
    
    #require_once 'classes/CDH_awsDBCluster.php';  

    require_once 'classes/CDH_awsListener.php';       
    require_once 'defines/CDH_awsDefinesAll.php';
    require_once 'defines/services_aws/CDH_awsDefineStep0NetSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep1FileSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep3ImageSystem.php';
    #require_once 'defines/services_aws/CDH_awsDefineStep4LoadBalancingSystem.php';
    #require_once 'defines/services_aws/CDH_awsDefineStep5AutoScalingGroupFrontEndSystem.php';
    #require_once 'defines/services_aws/CDH_awsDefineStep6AutoScalingGroupBackEndSystem.php';
    #require_once 'defines/services_aws/CDH_awsDefineStep7MoveDbLocalToCloudSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep9VpnSystem.php';

    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsStep9VpnSystem extends CDH_abstractAwsCloudator{
        
        #private $clientCwt;
        #private $subnet0Name;
        #private $subnet1Name;
        #private $subnet2Name;
        

        public function __construct(){
            /*
            if ( touch( CDH_IMAGE_FILE_USER_DATA ) == false){
                $this->logUser('{"eleven": "\nL035 No touched file. " . CDH_IMAGE_FILE_USER_DATA . "\n";
                die('\nL036 Problemas touching\n');
            }
            */

            #$this->createNames();

        }

    
        public function createForCloudator() {

            #region Conect
             
            $service = $this->serviceConect();
            if( $service == false ) {
                $this->logAdmin("L080 - KO Error en serviceFilesLocalToCloudConect\n");
                return false;
            }
            #endregion Conect
            
            #region Transmission
            $service = $this->serviceTransmission();
            if( $service == false ){ 
                $this->logAdmin("L087 - KO serviceFilesLocalToCloudTransmission no creado\n");
                return false;
            }
            $this->logUser('{"eleven": "\nL088 - OK serviceLocal2CloudTransmission finalizado\n"},');
            #endregion Transmission

            #region Ending   
            $service = $this->serviceEnding  ();
            if( $service == false ) {
                $this->logUser('{"eleven": "\nL095 - KO serviceFilesLocalToCloudEnding no borrado\n" },');
                return true;
            }
            $this->logUser('{"eleven": "\nL096 - OK serviceLocal2CloudEnding finalizado\n"},');
            #endregion Ending

            $this->logUser('{"eleven": "\nL156 - OK TODO serviceFilesLocalToCloud creado\n"},');

            return true;
                        
        }
        
        private function serviceConect(){
            
            $this->createPolicy               ();
            $this->logUser('{"eleven":("\nL103 policy creada")},');
            sleep(5);

            $this->createRole                 ();
            $this->logUser('{"eleven":("\nL105 role creado")},');            
            sleep(5);

            $this->createSecurityGroup        ();
            $this->logUser('{"eleven":("\nL107 sg creado")},');
            sleep(5);

            $this->createKeyPair              ();
            $this->logUser('{"eleven":("\nL103 keyPair creado")},');
            sleep(5);

            return true;
        }
        
        private function serviceTransmission(){
            $this->createElasticIp              ();
            $this->logUser('{"eleven":("\nL117 Elastic Ip creada")},');
            sleep(5);

            $this->createInstance               ();
            $this->logUser('{"eleven":("\nL118 Instance creada")},');
            sleep(5);
            
            $this->associateElasticIpToInstance ();
            $this->logUser('{"eleven":("\nL121 Asociada Ip a Instancia")},');
            sleep(5);
            
            return true;
        }

   


        #region serviceConnect

        public function createPolicy(){
            $service        = new CDH_awsPolicy();
            $data           = CDH_VPN_createPolicy;
            #return true;
            $service        = $service->create( $data );
            if( $service == false){
                 $this->logAdmin("\nL150 - Policy no creado\n");
                 return false;
            }
            $this->logUser('{"eleven": "\nL151 - Policy creado\n"},');
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
                'InstanceProfileName'   => CDH_VPN_createRole['RoleName'], // REQUIRED
                'RoleName'              => CDH_VPN_createRole['RoleName'], // REQUIRED
            ];

            $dataAttachRolePolicy = [
                   'PolicyArn' => $policy->getArnByName( CDH_VPN_createPolicy['PolicyName'] ), // REQUIRED
                   'RoleName' => CDH_VPN_createRole['RoleName'], // REQUIRED
            ]; 

            #print_r($dataAddRoleToInstanceProfile);
            #print_r($dataAttachRolePolicy);
            #die("\nL172 \n");

            $resultRole             = $role->create                     ( CDH_VPN_createRole );
            sleep(2);
            $resultInstanceProfile  = $instanceProfile->create          ( CDH_VPN_createInstanceProfile );
            sleep(2);
            $resultAddRole          = $role->addRoleToInstanceProfile   ( $dataAddRoleToInstanceProfile );
            sleep(2);
            $resultAttachRolePolicy = $role->attachRolePolicy( $dataAttachRolePolicy );

            
            return true;


        }

        public function createSecurityGroup(){
            $vpc            = new CDH_awsVpc();
            $securityGroup  = new CDH_awsSecurityGroup();
            
            $data       = CDH_VPN_createSecurityGroup;
            $dataRules  = CDH_VPN_authorizeSecurityGroupIngress;

            $data['VpcId']  = $vpc->getIdByName( CDH_VPC_NAME );

            $result = $securityGroup->create( $data );

            $dataRules['GroupId'] = $result['GroupId'];

            $result = $securityGroup->authorizeSecurityGroupIngress( $dataRules );
        
            return true;  

        }



        public function createKeyPair(){
            $service        = new CDH_awsKeyPair();
            $service        = $service->create( CDH_VPN_createKeyPair, CDH_VPN_sshAccessFilePath );
            #return true;
            if( $service == false){
                 $this->logAdmin("\nL252 - KEYPAIR no creado\n");
                 return false;
            }
                
            return true;
        }

        #endregion serviceConnect


        #region serviceTransmission
        public function createElasticIp(){
            $service    = new CDH_awsElasticIp();
            $service    = $service->create( CDH_VPN_createElasticIp );
            if( $service == false){
                 $this->logAdmin ("\nL211 - Ellastic Ip no creado\n");
                 return false;
            }
            
            return true;
        }

        private function createInstance(){

            $instance   = new CDH_awsInstance();
            #$bucket     = new CDH_awsBucket();
            $ip         = new CDH_awsInstanceProfile();
            $sg         = new CDH_awsSecurityGroup();
            $subnet     = new CDH_awsSubnet();
            $image      = new CDH_awsImage();
            #$elasticIp  = new CDH_awsElasticIp();
        
            $ipService          = $ip->getServiceByName         ( CDH_VPN_createInstanceProfile['InstanceProfileName'] );
            #$elasticIpService   = $elasticIp->getServiceByName  ( CDH_VPN_createElasticIp['TagSpecifications'][0]['Tags'][0]['Value'] );

            #$ipArn          = $ipService['Arn'];

            $sgService      = $sg->getServiceByName     (   CDH_VPN_createSecurityGroup  ['GroupName']  );
            $sgId           = $sgService['GroupId'];
            
            $subnetName     = CDH_NET_SYSTEM_SUBNETS[2]['TagSpecifications'][0]['Tags'][0]['Value'];
            $subnetService  = $subnet->getServiceByName ($subnetName);
            $subnetId       = $subnetService['SubnetId'];
            
            $CDH_INSTANCE                                         = CDH_VPN_createInstance;
            $CDH_INSTANCE['IamInstanceProfile']['Arn']            = $ipService['Arn'];
            $CDH_INSTANCE['NetworkInterfaces'][0]['Groups'][0]    = $sgId;
            $CDH_INSTANCE['NetworkInterfaces'][0]['SubnetId']     = $subnetId;
            //$CDH_INSTANCE['ImageId']                              = $image->getIdByName( CDH_IMAGE_INSTANCE['TagSpecifications'][0]['Tags'][0]['Value'] );
            
            $instances  = $instance->create( $CDH_INSTANCE );
            
            if( $instances == false || !isset($instances[0]['InstanceId']) )
                return false;
                            
            $instanceName = $CDH_INSTANCE['TagSpecifications'][0]['Tags'][0]['Value'];

            $instanceId = $instance->getIdByName( $instanceName );

            print_r ( "\n\$instanceId = $instanceId" );

            return $instanceId;

        }

        private function associateElasticIpToInstance(){
            
            $instance   = new CDH_awsInstance();     
            $elasticIp  = new CDH_awsElasticIp();
            $clientEc2  = $instance->getClientEc2();
            
            $instanceObject = $instance->getServiceByName   (CDH_VPN_createInstance['TagSpecifications'][0]['Tags'][0]['Value'] );
            $elasticIpObject= $elasticIp->getServiceByName  (CDH_VPN_createElasticIp['TagSpecifications'][0]['Tags'][0]['Value']);
            
            $instanceId  = $instanceObject ['InstanceId'];
            $elasticIpId = $elasticIpObject['AllocationId'];

            if( !strpos( $instanceId, "i-") === 0  ){
                $this->logAdmin("L302 - instanceId no es correcto o no existe");
                return false;
            }
            
            if( !strpos( $elasticIpId, "eipalloc-") === 0  ){
                $this->logUser('{"eleven": "\nL308 - \$elasticIpId no es correcto o no existe"},');
                return false;
            }

            $clientEc2->associateAddress([
                'AllocationId'  => $elasticIpId,
                'InstanceId'    => $instanceId,
            ]);

            return true;

        }
        #endregion serviceTransmission

        #region serviceEnding

        private function serviceEnding(){  
            $this->logUser('{"eleven":("\nL131 OK VPN Creado")},');
            sleep(5);
            return true;
        }

        #endregion serviceEnding

    }





?>
