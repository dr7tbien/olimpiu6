<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsCloudator.php';

    #require_once 'classes/CDH_awsEfs.php'; 
    require_once 'classes/CDH_awsSecurityGroup.php'; 
    require_once 'classes/CDH_awsMountTarget.php';   
    require_once 'classes/CDH_awsAccessPoint.php';   



    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
 

    class CDH_awsStep1FileSystem extends CDH_abstractAwsCloudator{
        
        
        #private $subnet0Name;
        #private $subnet1Name;
        #private $subnet2Name;
        

        public function __construct(){
            /*
            if ( touch( CDH_IMAGE_FILE_USER_DATA ) == false){
                $this->logUser('{"three":"\nL035 No touched file. " . CDH_IMAGE_FILE_USER_DATA . "\n";
                die('\nL036 Problemas touching\n');
            }
            */
            $this->createNames();
            
        }

    
        public function createForCloudator() {
            #region FileSystem
            $service = $this->createFileSystem();
            if( $service == false ) {
                $this->logUser('{"three":"\nL051 - FileSystem no creado\n"},');
                return false;
            }
            $this->logUser('{"three":"\nL054 - FileSystem creado\n"},');;
            #endregion FileSystem
            
            #region SecurityGroup
            $service = $this->createSecurityGroupFileSystem(); 
            if( $service == false ) {
                $this->logUser('{"three":"\nL060 - Security Group no creado\n"},');
                return false;
            }
            $this->logUser('{"three":"\nL063 - Security Group creado\n"},');          
            #endregion SecurityGroup
            
            #region MountTargets
            $service = $this->createMountTargets(); 
            if( $service == false ) {
                $this->logUser('{"three":"\nL069 - Target Service no creado\n"},');
                return false;
            }
            $this->logUser('{"three":"\nL072 - Target Service creado\n"},');
            #endregion MountTargets
            
            #region AccessPoint
            $service = $this->createAccessPoint(); 
            if( $service == false ) {
                $this->logUser('{"three":"\nL078 - Access Point Service no creado\n"},');
                return false;
            }
            $this->logUser('{"three":"\nL081 - Access Point Service creado\n"},');         
            #endregion AccessPoint
            
            $this->logUser('{"three":"\nL096 - TODO FILESYSTEM creado\n"},');

            return true;
                        
        }


        public function createFileSystem(){
            $service        = new CDH_awsFileSystem();
            $data           = CDH_FILESYSTEM;

            $service        = $service->create( $data );
            if( $service == false){
                $this->logUser('{"three":"\nL087 - FileSystem no creado\n"},');
                return false;
            }
            
            return true;
        }

        public function createSecurityGroupFileSystem(){
            $service        = new CDH_awsSecurityGroup();
            $vpc            = new CDH_awsVpc();
            $data           = CDH_FILESYSTEM_SECURITY_GROUP;
            
            $data['VpcId']  = $vpc->getIdByName( $this->getVpcName() );

            $service            = $service->create( $data );
            if( $service == false){
                $this->logUser('{"three":"\nL123 - SG no creado\n"},');
                return false;
            }

            $service            = new CDH_awsSecurityGroup();
            $data               = CDH_FILESYSTEM_AUTHORIZE_SECURITY_GROUP_INGRESS;
            
            #$data['GroupId']    = $service->getIdByName( $this->getSecurityGroupNameFileSystem() );
            $data['GroupId']    = $service->getIdByName( $this->getSecurityGroupNameFileSystem() );

            $service = $service->authorizeSecurityGroupIngress( $data );

            if( $service == false){
                $this->logUser('{"three":"\nL135 - Rules para SG no creadas\n"},');
                return false;
            }

            return true;            
        }
        

        public function createMountTargets(){
             
            $service    = new CDH_awsMountTarget();
            $subnet     = new CDH_awsSubnet();
            $fileSystem = new CDH_awsFileSystem();
            $sg         = new CDH_awsSecurityGroup();

            $fileSystemId   = $fileSystem->getIdByName  ( CDH_FILESYSTEM['Tags'][0]['Value']  ); 
            $sgId           = $sg->getIdByName   ( $this->getSecurityGroupNameFileSystem()  ); 
            
            $data_0 = $data_1 = $data_2 = CDH_FILESYSTEM_MOUNT_TARGET; 
 
            $data_0['FileSystemId']     = $data_1['FileSystemId']       = $data_2['FileSystemId']       = $fileSystemId;
            $data_0['SecurityGroups'][] = $data_1['SecurityGroups'][]   = $data_2['SecurityGroups'][]   = $sgId;

            $data_0['SubnetId'] = $subnet->getIdByName( $this->getSubnetPrivate0Name() );
            $data_1['SubnetId'] = $subnet->getIdByName( $this->getSubnetPrivate1Name() );
            $data_2['SubnetId'] = $subnet->getIdByName( $this->getSubnetPrivate2Name() );

            $res        = $service->create( $data_0 );
            if( $res == false){
                $this->logUser('{"three":"\nL184 - MountTarget 0 no creado\n"},');
                return false;
            }
            
            $res        = $service->create( $data_1 );
            if( $res == false){
                $this->logUser('{"three":"\nL190 - MountTarget 1 no creado\n"},');
                return false;
            }
            
            $res        = $service->create( $data_2 );
            if( $res == false){
                $this->logUser('{"three":"\nL196 - MountTarget 2 no creado\n"},');
                return false;
            }
            
            return true;     
        }


        public function createAccessPoint(){
            $service    = new CDH_awsAccessPoint();
            $fileSystem        = new CDH_awsFileSystem();

            $CDH_FILESYSTEM_ACCESS_POINT            = CDH_FILESYSTEM_ACCESS_POINT;
            $CDH_FILESYSTEM_ACCESS_POINT['FileSystemId']   = $fileSystem->getIdByName( CDH_FILESYSTEM['Tags'][0]['Value']  );

            $service        = $service->create( $CDH_FILESYSTEM_ACCESS_POINT );
            
            if( $service == false){
                $this->logUser('{"three":"\nL087 - FileSystem no creado\n"},');
                return false;
            }
            
            return true;
            
        }


    }





?>
