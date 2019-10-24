<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
--------------------------------------------------------------------------------
HHIMS - Hospital Health Information Management System
Copyright (c) 2011 Information and Communication Technology Agency of Sri Lanka
<http: www.hhims.org/>
----------------------------------------------------------------------------------
This program is free software: you can redistribute it and/or modify it under the
terms of the GNU Affero General Public License as published by the Free Software 
Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,but WITHOUT ANY 
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR 
A PARTICULAR PURPOSE. See the GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License along 
with this program. If not, see <http://www.gnu.org/licenses/> 




---------------------------------------------------------------------------------- 
Date : June 2016
Author: Mr. Jayanath Liyanage   jayanathl@icta.lk

Programme Manager: Shriyananda Rathnayake
URL: http://www.govforge.icta.lk/gf/project/hhims/
----------------------------------------------------------------------------------
*/

	include("header.php");	///loads the html HEAD section (JS,CSS)
	echo Modules::run('menu'); //runs the available menu option to that usergroup
?>
	<div class="container" style="width:95%;">
		<div class="row" style="margin-top: 55px; padding-bottom: 10px; padding-top: 15px;">
            <table border="0" width="100%" >
                    <tr >
                        <td valign="top" class="leftmaintable">
		<?php echo Modules::run('leftmenu/procedure_room'); //runs the available left menu for preferance ?>
	                          </td>
                        <td valign="top" class="rightmaintable">
			<div class="panel panel-default"  >
                            <div class="modal fade" id="procedureorder" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"></div>
                            <div class="modal fade" id="collectionorder" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"></div>
                            <div class="modal fade" id="injectionorder" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true"></div>
				<div class="panel-heading"><b>Procedure order list</b></div>
				<div id="patient_list">
				<?php echo $pager;  ?>
				</div>
			</div>
                      </td>
                      </tr>
                      </table>     
		</div>
	</div>
</div>