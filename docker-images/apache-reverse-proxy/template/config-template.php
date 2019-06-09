<?php 
$dynamic_app = getenv('DYNAMIC_APP');
$static_app  = getenv('STATIC_APP');
$dynamic_app2 = getenv('DYNAMIC_APP2');
$static_app2  = getenv('STATIC_APP2');
?>

<VirtualHost *:80>
	ServerName demo.res.ch

	Header add Set-Cookie "ROUTEID=.%{BALANCER_WORKER_ROUTE}e; path=/" env=BALANCER_ROUTE_CHANGED
	<Proxy "balancer://cities">
    		BalancerMember "http://<?php print $dynamic_app ?>" route=1
    		BalancerMember "http://<?php print $dynamic_app2 ?>" route=2
		ProxySet stickysession=ROUTEID
	</Proxy>

	ProxyPass '/api/cities/' 'balancer://cities/'
	ProxyPassReverse '/api/cities/' 'balancer://cities/'

	<Proxy "balancer://static">
    		BalancerMember "http://<?php print $static_app ?>"
    		BalancerMember "http://<?php print $static_app2 ?>"
	</Proxy>
	
	ProxyPass '/' 'balancer://static/'
	ProxyPassReverse '/' 'balancer://static/'
</VirtualHost>
