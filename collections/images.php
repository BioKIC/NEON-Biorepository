<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceImageManager.php');

$imagePageNumber = array_key_exists('imagepage', $_REQUEST) ? filter_var($_REQUEST['imagepage'], FILTER_SANITIZE_NUMBER_INT) : 1;
$cntPerPage = array_key_exists('cntperpage', $_REQUEST) ? filter_var($_REQUEST['cntperpage'], FILTER_SANITIZE_NUMBER_INT) : 100;

$imageManager = new OccurrenceImageManager();
$searchVar = $imageManager->getQueryTermStr();
$searchVar .= '&tabindex=3';
?>
<div id="imagesdiv">
	<div id="imagebox">
		<?php
		$imageArr = $imageManager->getImageArr($imagePageNumber, $cntPerPage);
		$recordCnt = $imageManager->getRecordCnt();
		if($imageArr){
			echo '<div style="clear:both;margin:5 0 5 0;"><hr /></div>';
			$lastPage = ceil($recordCnt / $cntPerPage);
			$startPage = ($imagePageNumber > 4?$imagePageNumber - 4:1);
			$endPage = ($lastPage > $startPage + 9?$startPage + 9:$lastPage);
			$url = $CLIENT_ROOT . '/collections/list.php?' . $searchVar;
			$pageBar = '<div style="float:left" >';
			if($startPage > 1){
				$pageBar .= '<span class="pagination" style="margin-right:5px;"><a href="'.$url.'&imagepage=1">First</a></span>';
				$pageBar .= '<span class="pagination" style="margin-right:5px;"><a href="'.$url.'&imagepage='.(($imagePageNumber - 10) < 1 ?1:$imagePageNumber - 10).'">&lt;&lt;</a></span>';
			}
			for($x = $startPage; $x <= $endPage; $x++){
				if($imagePageNumber != $x){
					$pageBar .= '<span class="pagination" style="margin-right:3px;"><a href="'.$url.'&imagepage='.$x.'">'.$x.'</a></span>';
				}
				else{
					$pageBar .= "<span class='pagination' style='margin-right:3px;font-weight:bold;'>".$x."</span>";
				}
			}
			if(($lastPage - $startPage) >= 10){
				$pageBar .= '<span class="pagination" style="margin-left:5px;"><a href="'.$url.'&imagepage='.(($imagePageNumber + 10) > $lastPage?$lastPage:($imagePageNumber + 10)).'">&gt;&gt;</a></span>';
				if($recordCnt < 10000) $pageBar .= '<span class="pagination" style="margin-left:5px;"><a href="'.$url.'&imagepage='.$lastPage.'">Last</a></span>';
			}
			$pageBar .= '</div><div style="float:right;margin-top:4px;margin-bottom:8px;">';
			$beginNum = ($imagePageNumber - 1)*$cntPerPage + 1;
			$endNum = $beginNum + $cntPerPage - 1;
			if($endNum > $recordCnt) $endNum = $recordCnt;
			$pageBar .= 'Page ' . $imagePageNumber . ', records ' . number_format($beginNum) . '-' . number_format($endNum) . ' of ' . number_format($recordCnt) . '</div>';
			$paginationStr = $pageBar;
			echo '<div style="width:100%;">'.$paginationStr.'</div>';
			echo '<div style="clear:both;margin:5 0 5 0;"><hr /></div>';
			echo '<div style="width:98%;margin-left:auto;margin-right:auto;">';
			foreach($imageArr as $imgArr){
				$imgId = $imgArr['imgid'];
				$imgUrl = $imgArr['url'];
				$imgTn = $imgArr['thumbnailurl'];
				if($imgTn){
					$imgUrl = $imgTn;
					if($IMAGE_DOMAIN && substr($imgTn,0,1)=='/') $imgUrl = $IMAGE_DOMAIN.$imgTn;
				}
				elseif($IMAGE_DOMAIN && substr($imgUrl,0,1)=='/'){
					$imgUrl = $IMAGE_DOMAIN.$imgUrl;
				}
				?>
				<div class="tndiv" style="margin-bottom:15px;margin-top:15px;">
					<div class="tnimg">
						<?php
						$anchorLink = '';
						if($imgArr['occid']){
							$anchorLink = '<a href="#" onclick="openIndPU('.$imgArr['occid'].');return false;">';
						}
						else{
							$anchorLink = '<a href="#" onclick="openImagePopup('.$imgId.');return false;">';
						}
						echo $anchorLink.'<img src="'.$imgUrl.'" /></a>';
						?>
					</div>
					<div>
						<?php
						if($sciname = $imgArr['sciname']){
							if(strpos($imgArr['sciname'],' ')) $sciname = '<i>'.$sciname.'</i>';
							if($imgArr['tid']) echo '<a href="'.$CLIENT_ROOT.'/taxa/index.php?tid='.$imgArr['tid'].'">';
							echo $sciname;
							if($imgArr['tid']) echo '</a>';
							echo '<br />';
						}
						if($imgArr['occid']){
							echo '<a href="'.$CLIENT_ROOT.'/collections/individual/index.php?occid='.$imgArr['occid'].'"><b>Full Record Details</b></a>';
						}
						?>
					</div>
				</div>
				<?php
			}
			echo '</div>';
			if($lastPage > $startPage){
				echo "<div style='clear:both;margin:5 0 5 0;'><hr /></div>";
				echo '<div style="width:100%;">'.$paginationStr.'</div>';
			}
			?>
			<div style="clear:both;"></div>
			<?php
		}
		else{
			echo '<h3>No images exist matching your search criteria. Please modify your search and try again.</h3>';
		}
		?>
	</div>
</div>
