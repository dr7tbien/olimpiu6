<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    

    require_once 'defines/services_aws/CDH_awsDefineStep0NetSystem.php';
    require_once 'defines/services_aws/CDH_awsDefineStep2DbSystem.php';
    require_once 'defines/CDH_awsDefinesAll.php';



    require_once 'classes/CDH_abstractAwsCloudator.php';
           
    require_once 'classes/CDH_awsDBCluster.php';
    require_once 'classes/CDH_awsDBClusterParameterGroup.php';
    require_once 'classes/CDH_awsDBParameterGroup.php';
    require_once 'classes/CDH_awsDBSubnetGroup.php';
    require_once 'classes/CDH_awsDBInstance.php';
    require_once 'classes/CDH_awsOptionGroup.php';
    require_once 'classes/CDH_awsSecurityGroup.php';
    require_once 'classes/CDH_awsVpc.php';
    require_once 'classes/CDH_awsSubnet.php';
 
    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsStep2DbSystem extends CDH_abstractAwsCloudator{
        
        
        #private $subnet0Name;
        #private $subnet1Name;
        #private $subnet2Name;
        

        public function __construct(){
            $this->createNames();

        }

    
        public function createForCloudator() {

            #region SecurityGroupDb
            $service = $this->createSecurityGroup();
            if( $service == false ) {
                $this->logUser('{"four":"\nL042 - SG no creado\n"},');
                return false;
            }
            $this->logUser('{"four":"\nL045 - SG Db creado\n"},');
            #endregion SecurityGroupDb
            sleep(5);
            #region ClusterParameterGroup
            $service = $this->createDBClusterParameterGroup(); 
            if( $service == false ) {
                $this->logUser('{"four":"\nL051 - ClusterParameterGroup no creado\n"},');
                return false;
            }
            $this->logUser('{"four":"\nL054 - ClusterParameterGroup creado\n"},');            
            #endregion ClusterParameterGroup
            sleep(5);
            #region DbParameterGroup
            $service = $this->createDBParameterGroup(); 
            if( $service == false ) {
                $this->logUser('{"four":"\nL060 - DbParameterGroup no creado\n"},');
                return false;
            }
            $this->logUser('{"four":"\nL063 - DbParameterGroup creado\n"},');
            #endregion DbParameterGroup
            sleep(5);
            #region DbSubnetGroup
            $service = $this->createDBSubnetGroup(); 
            if( $service == false ) {
                $this->logUser('{"four":"\nL069 - DbSubnetGroup no creado\n"},');
                return false;
            }
            $this->logUser('{"four":"\nL072 - DbSubnetGroup creado\n"},');            
            #endregion DbSubnetGroup
            sleep(5);
            #region OptionGroup
            $service = $this->createOptionGroup(); 
            if( $service == false ) {
                $this->logUser('{"four":"\nL078 - OptionGroup no creado\n"},');
                return false;
            }
            $this->logUser('{"four":"\nL081 - OptionGroup creado\n"},');            
            #endregion OptionGroup

            sleep(5);

            #region dbCluster
            $service = $this->createDBCluster(); 
            if( $service == false ) {
                $this->logUser('{"four":"\nL097 - dbCluster Service no creado\n"},');
                return false;
            }
            $this->logUser('{"four":"\nL100 - dbCluster Service creado\n"},');           
            #endregion dbCluster

            sleep(5);

            #region InstanceDb
            $service = $this->createDbInstance(); 
            if( $service == false ) {
                $this->logUser('{"four":"\nL106 - Instance DB no creado\n"},');
                return false;
            }
            $this->logUser('{"four":"\nL109 - Instance DB creado\n"},');           
            #endregion InstanceDb

            $this->logUser('{"four":"\nL111 - TODO DB creado\n"},');

            return true;
                        
        }



        public function createSecurityGroup(){
            $vpc        = new CDH_awsVpc();
            $service    = new CDH_awsSecurityGroup();
            $data       = CDH_DB_INSTANCE_createSecurityGroup;
            $dataRules  = CDH_DB_INSTANCE_createRulesIngress;

            #$data['VpcId']  = $vpc->getIdByName( $this->getVpcName() );
            
            $data['VpcId']  = $vpc->getIdByName( CDH_VPC_NAME );


            $result = $service->create( $data );


            if( $result == false ){
                $this->logUser('{"four":"\nL132 - SG no creadas\n"},');
                return false;
            }
            $dataRules['GroupId'] = $result['GroupId'];

            $result = $service->authorizeSecurityGroupIngress( $dataRules );
    
            if( $result == false){
                $this->logUser('{"four":"\nL143 - Rules para SG no creadas\n"},');
                return false;
            }
    
            return true;            

        }

        public function createDBClusterParameterGroup(){
            $service    = new CDH_awsDBClusterParameterGroup();
            $data = CDH_DB_INSTANCE_createDBClusterParameterGroup;
            
            $result     = $service->create($data);
            if( $result == false ){
                $this->logUser('{"four":"\nL157 - ClusterParameterGroup no creadas\n"},');
                return false;
            }

            return true;
        }

        public function createDBParameterGroup(){
            $service    = new CDH_awsDBParameterGroup();
            $data = CDH_DB_INSTANCE_createDBParameterGroup;
            
            $result     = $service->create($data);
            if( $result == false ){
                $this->logUser('{"four":"\nL170 - DBParameterGroup no creadas\n"},');
                return false;
            }

            return true;
        }

        public function createDBSubnetGroup(){
            $service    = new CDH_awsDBSubnetGroup();
            $data       = CDH_DB_INSTANCE_createDBSubnetGroup;
            $subnet     = new CDH_awsSubnet();

            $data['SubnetIds'][]     = $subnet->getIdByName( $this->getSubnetDatabase0Name() );
            $data['SubnetIds'][]     = $subnet->getIdByName( $this->getSubnetDatabase1Name() );
            $data['SubnetIds'][]     = $subnet->getIdByName( $this->getSubnetDatabase2Name() );

            $result     = $service->create($data);
            if( $result == false ){
                $this->logUser('{"four":"\nL183 - DBSubnetGroup no creadas\n"},');
                return false;
            }

            return true;
        }

        public function createOptionGroup(){
            $service    = new CDH_awsOptionGroup();
            $data = CDH_DB_INSTANCE_createOptionGroup;
            
            $result     = $service->create($data);
            if( $result == false ){
                $this->logUser('{"four":"\nL196 - OptionGroup no creadas\n"},');
                return false;
            }

            return true;
        }

        public function createDBCluster(){
            $service    = new CDH_awsDBCluster      ();
            $data       = CDH_DB_INSTANCE_createDBCluster;
            //$userData   = new CDH_PS_userDataChecker();
            $sg         = new CDH_awsSecurityGroup();

            #$sgId       = $sg->getIdByName( $this->getSecurityGroupNameDb() );
            $sgId       = $sg->getIdByName( CDH_DB_INSTANCE_createSecurityGroup['GroupName'] );
            $data['AvailabilityZones'][]    = CDH_AVAILABILITY_ZONE_A;
            $data['AvailabilityZones'][]    = CDH_AVAILABILITY_ZONE_B;
            $data['AvailabilityZones'][]    = CDH_AVAILABILITY_ZONE_C;
            $data['DatabaseName']           = CDH_CMS_DB_NAME;
            $data['MasterUsername']         = CDH_CMS_DB_USER;
            $data['MasterUserPassword']     = CDH_CMS_DB_PASSWD;            
            $data['VpcSecurityGroupIds'][]  =  $sgId;
        
            #var_export( $data );
            


            $result     = $service->create($data);
            if( $result == false ){
                $this->logUser('{"four":"\nL236 - DBCluster no creadas\n"},');
                return false;
            }

            return true;
        }



        public function createDBInstance(){
            $service    = new CDH_awsDBInstance();
            $data = CDH_DB_INSTANCE_createDBInstance;
            
            $result     = $service->create($data);
            if( $result == false ){
                $this->logUser('{"four":"\nL170 - DBSubnetGroup no creadas\n"},');
                return false;
            }

            return true;
        }

    }
?>