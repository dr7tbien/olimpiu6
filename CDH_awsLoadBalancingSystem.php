<?php
    /**
    * Just another function to manage vpcs.
    * @author dr7tbien <dr7tbien#gmail.com>
    * @version 1.0  
    */
    
    #require_once 'classes/CDH_abstractAwsService.php';
    require_once 'defines/services_aws/CDH_awsDefineLoadBalancingSystem.php';

    require_once 'classes/CDH_awsVpc.php';        
    require_once 'classes/CDH_awsSecurityGroup.php';        
    require_once 'classes/CDH_awsTargetGroup.php';        
    require_once 'classes/CDH_awsLoadBalancer.php';   
    require_once 'classes/CDH_awsListener.php';   
         


    use Aws\Exception\AwsException;
    use Aws\Credentials\Credentials;
    

    class CDH_awsLoadBalancingSystem {
        
        
        public function __construct(){
         
        }
        
        public function createForCloudator() {
            $vpc    = new CDH_awsVpc();
            $sg     = new CDH_awsSecurityGroup();
            $tg     = new CDH_awsTargetGroup();
            $lb     = new CDH_awsLoadBalancer();
            $li     = new CDH_awsListener();


            $vpcId                              = $vpc->getIdByName(  CDH_VPC_NAME  ); 
            $LOAD_BALANCING_SYSTEM_SG           = LOAD_BALANCING_SYSTEM_SG;
            $LOAD_BALANCING_SYSTEM_SG['VpcId']  = $vpcId;
            $groupId                            = $sg->create( $LOAD_BALANCING_SYSTEM_SG );
            
            $tg->create( LOAD_BALANCING_SYSTEM_TG_080 );
            $tg->create( LOAD_BALANCING_SYSTEM_TG_443 );
            $lb->create( LOAD_BALANCING_SYSTEM_LOAD_BALANCER );
            $li->create( LOAD_BALANCING_SYSTEM_LISTENER_080);
            $li->create( LOAD_BALANCING_SYSTEM_LISTENER_443);

        }

        

        



    }

?>
