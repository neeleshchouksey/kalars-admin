<?php
$session = session();
if( $session->get('klrdmin_id') && $session->get('admin_email'))
{
?>
<!--Footer : Start-->
	<div id="footer">&copy; KHMTravel TM. All Rights Reserved. KHMTravel TM is a pending trademark registration of KHM Consulting. Inc.<br/>
	</div>
<!--Footer : End-->
<?php
}
?>

</div>
<!-- wrapper div ends here-->

<div id="fade"></div>
<?php //$this->output->enable_profiler(false); // Profiler. ?>
</body>
</html>