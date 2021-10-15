<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    require_once 'classes/CDH_abstractAwsCloudator.php';

    require_once 'classes/CDH_awsEfs.php'; 
    require_once 'classes/CDH_awsSecurityGroup.php'; 
    require_once 'classes/CDH_awsMountTarget.php';   
    require_once 'classes/CDH_awsAccessPoint.php';   
    require_once 'defines/services_aws/CDH_awsDefineStep1EfsSystem.php';



    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsStep1EfsSystem extends CDH_abstractAwsCloudator{
        
        
        #private $subnet0Name;
        #private $subnet1Name;
        #private $subnet2Name;
        

        public function __construct(){
            $this->createNames();

        }

    
        public function createForCloudator() {
            #region FileSystem
            $service = $this->createFileSystem();
            if( $service == false ) {
                echo "\nL051 - FileSystem no creado\n";
                return false;
            }
            echo "\nL054 - FileSystem creado\n";
            #endregion FileSystem
            
            #region SecurityGroup
            $service = $this->createSecurityGroupEfs(); 
            if( $service == false ) {
                echo "\nL060 - Security Group no creado\n";
                return false;
            }
            echo "\nL063 - Security Group creado\n";            
            #endregion SecurityGroup
            
            #region MountTargets
            $service = $this->createMountTargets(); 
            if( $service == false ) {
                echo "\nL069 - Target Service no creado\n";
                return false;
            }
            echo "\nL072 - Target Service creado\n";
            #endregion MountTargets
            
            #region AccessPoint
            $service = $this->createAccessPoint(); 
            if( $service == false ) {
                echo "\nL078 - Access Point Service no creado\n";
                return false;
            }
            echo "\nL081 - Access Point Service creado\n";            
            #endregion AccessPoint
            
            echo "\nL096 - TODO EFS creado\n";

            return true;
                        
        }


        public function createFileSystem(){
            $service        = new CDH_awsEfs();
            $data           = CDH_EFS_SYSTEM_FILE_SYSTEM;
            $service        = $service->create( $data );
            if( $service == false){
                echo "\nL087 - FileSystem no creado\n";
                return false;
            }
            
            return true;
        }

        public function createSecurityGroupEfs(){
            $service        = new CDH_awsSecurityGroup();
            $vpc            = new CDH_awsVpc();
            $data           = CDH_EFS_SYSTEM_SECURITY_GROUP;
            
            $data['VpcId']  = $vpc->getIdByName( $this->getVpcName() );

            $service            = $service->create( $data );
            if( $service == false){
                echo "\nL123 - SG no creado\n";
                return false;
            }

            $service            = new CDH_awsSecurityGroup();
            $data               = CDH_EFS_SYSTEM_AUTHORIZE_SECURITY_GROUP_INGRESS;
            
            $data['GroupId']    = $service->getIdByName( $this->getSecurityGroupNameEfs() );

            $service = $service->authorizeSecurityGroupIngress( $data );

            if( $service == false){
                echo "\nL135 - Rules para SG no creadas\n";
                return false;
            }

            return true;            
        }
        

        public function createMountTargets(){
             
            $service    = new CDH_awsMountTarget();
            $subnet     = new CDH_awsSubnet();
            $efs        = new CDH_awsEfs();
            $sg         = new CDH_awsSecurityGroup();

            $efsId         = $efs->getIdByName  ( $this->getEfsName()               ); 
            $sgId          = $sg->getIdByName   ( $this->getSecurityGroupNameEfs()  ); 
            
            $data_0 = $data_1 = $data_2 = CDH_EFS_SYSTEM_MOUNT_TARGET; 
 
            $data_0['FileSystemId']     = $data_1['FileSystemId']       = $data_2['FileSystemId']       = $efsId;
            $data_0['SecurityGroups'][] = $data_1['SecurityGroups'][]   = $data_2['SecurityGroups'][]   = $sgId;

            $data_0['SubnetId'] = $subnet->getIdByName( $this->getSubnetPrivate0Name() );
            $data_1['SubnetId'] = $subnet->getIdByName( $this->getSubnetPrivate1Name() );
            $data_2['SubnetId'] = $subnet->getIdByName( $this->getSubnetPrivate2Name() );

            $res        = $service->create( $data_0 );
            if( $res == false){
                echo "\nL184 - MountTarget 0 no creado\n";
                return false;
            }
            
            $res        = $service->create( $data_1 );
            if( $res == false){
                echo "\nL190 - MountTarget 1 no creado\n";
                return false;
            }
            
            $res        = $service->create( $data_2 );
            if( $res == false){
                echo "\nL196 - MountTarget 2 no creado\n";
                return false;
            }
            
            return true;     
        }


        public function createAccessPoint(){
            $service    = new CDH_awsAccessPoint();
            $efs        = new CDH_awsEfs();

            $CDH_EFS_SYSTEM_ACCESS_POINT            = CDH_EFS_SYSTEM_ACCESS_POINT;
            $CDH_EFS_SYSTEM_ACCESS_POINT['FileSystemId']   = $efs->getIdByName( $this->getEfsName()  );

            $service        = $service->create( $CDH_EFS_SYSTEM_ACCESS_POINT );
            
            if( $service == false){
                echo "\nL087 - FileSystem no creado\n";
                return false;
            }
            
            return true;
            
        }


    }





?>
