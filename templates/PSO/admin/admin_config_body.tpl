<br clear="all" />

<h1>General Configuration</h1>

<p>The form below will allow you to customize all the general board options. For User and Forum configurations use the related links on the left hand side.</p>

<form action="{S_CONFIG_ACTION}" method="POST">

<table width="99%" cellpadding="1" cellspacing="0" border="0" align="center">
	<tr>
		<td class="tablebg" width="100%"><table width="100%" cellpadding="4" cellspacing="1" border="0">
			<tr>
				<td class="cat" colspan="2"><span class="cattitle">General Board Settings</span></td>
			</tr>
			<tr>
				<td class="row1">Site Name:</td>
				<td class="row2"><input type="text" size="25" maxlength="100" name="sitename" value="{SITENAME}"></td>
			</tr>
			<tr>
				<td class="row1">Enable account activation:</td>
				<td class="row2"><input type="radio" name="require_activation" value="1" {ACTIVATION_YES}>Yes&nbsp;&nbsp;<input type="radio" name="require_activation" value="0" {ACTIVATION_NO}>No</td>
			</tr>
			<tr>
				<td class="row1">Flood Interval: <br /><span class="gensmall">Number of seconds a user must wait between posts</span></td>
				<td class="row2"><input type="text" size="3" maxlength="4" name="flood_interval" value="{FLOOD_INTERVAL}"></td>
			</tr>
			<tr>
				<td class="row1">Topics Per Page</td>
				<td class="row2"><input type="text" name="topics_per_page" size="3" maxlength="4" value="{TOPICS_PER_PAGE}"></td>
			</tr>
			<tr>
				<td class="row1">Posts Per Page</td>
				<td class="row2"><input type="text" name="posts_per_page" size="3" maxlength="4" value="{POSTS_PER_PAGE}"></td>
			</tr>
			<tr>
				<td class="row1">Hot Threshold</td>
				<td class="row2"><input type="text" name="hot_threshold" size="3" maxlength="4" value="{HOT_TOPIC}"></td>
			</tr>
			<tr>
				<td class="row1">Default Template:</td>
				<td class="row2">{TEMPLATE_SELECT}</td>
			</tr>
			<tr>
				<td class="row1">Default Theme:</td>
				<td class="row2">{THEME_SELECT}</td>
			</tr>
			<tr>
				<td class="row1">Default Language:</td>
				<td class="row2">{LANG_SELECT}</td>
			</tr>
			<tr>
				<td class="row1">Date Format:<br /><span class="gensmall">{L_DATE_FORMAT_EXPLAIN}</span></td>
				<td class="row2"><input type="text" maxlength="16" name="default_dateformat" value="{DEFAULT_DATEFORMAT}"></td>
			</tr>
			<tr>
				<td class="row1">System Timezone:</td>
				<td class="row2">{TIMEZONE_SELECT}</td>
			</tr>
			<tr>
				<td class="row1">Enable GZip Compression:</td>
				<td class="row2"><input type="radio" name="gzip_compress" value="1" {GZIP_YES}> Yes&nbsp;&nbsp;<input type="radio" name="gzip_compress" value="0" {GZIP_NO}> No</td>
			</tr>
			<tr>
				<td class="cat" colspan="2"><span class="cattitle">User/Forum Ability Settings</span></td>
			</tr>
			<tr>
				<td class="row1">Allow HTML</td>
				<td class="row2"><input type="radio" name="allow_html" value="1" {HTML_YES}> Yes&nbsp;&nbsp;<input type="radio" name="allow_html" value="0" {HTML_NO}> No</td>
			</tr>
			<tr>
				<td class="row1">Allow BBCode</td>
				<td class="row2"><input type="radio" name="allow_bbcode" value="1" {BBCODE_YES}> Yes&nbsp;&nbsp;<input type="radio" name="allow_bbcode" value="0" {BBCODE_NO}> No</td>
			</tr>
			<tr>
				<td class="row1">Allow Smilies</td>
				<td class="row2"><input type="radio" name="allow_smilies" value="1" {SMILE_YES}> Yes&nbsp;&nbsp;<input type="radio" name="allow_smilies" value="0" {SMILE_NO}> No</td>
			</tr>
			<tr>
				<td class="row1">Allow Signatures</td>
				<td class="row2"><input type="radio" name="allow_sig" value="1" {SIG_YES}> Yes&nbsp;&nbsp;<input type="radio" name="allow_sig" value="0" {SIG_NO}> No</td>
			</tr>
			<tr>
				<td class="row1">Allow Name Change</td>
				<td class="row2"><input type="radio" name="allow_namechange" value="1" {NAMECHANGE_YES}> Yes&nbsp;&nbsp;<input type="radio" name="allow_namechange" value="0" {NAMECHANGE_NO}> No</td>
			</tr>
			<tr>
				<td class="cat" colspan="2"><span class="cattitle">Avatar Settings</span></td>
			</tr>
			<tr>
				<td class="row1">Allow local gallery avatars</td>
				<td class="row2"><input type="radio" name="allow_avatar_local" value="1" {AVATARS_LOCAL_YES}> Yes&nbsp;&nbsp;<input type="radio" name="allow_avatar_local" value="0" {AVATARS_LOCAL_NO}> No</td>
			</tr>
			<tr>
				<td class="row1">Allow remote avatars <br /><span class="gensmall">Avatars linked from another website</span></td>
				<td class="row2"><input type="radio" name="allow_avatar_remote" value="1" {AVATARS_REMOTE_YES}> Yes&nbsp;&nbsp;<input type="radio" name="allow_avatar_remote" value="0" {AVATARS_REMOTE_NO}> No</td>
			</tr>
			<tr>
				<td class="row1">Allow avatar uploading</td>
				<td class="row2"><input type="radio" name="allow_avatar_upload" value="1" {AVATARS_UPLOAD_YES}> Yes&nbsp;&nbsp;<input type="radio" name="allow_avatar_upload" value="0" {AVATARS_UPLOAD_NO}> No</td>
			</tr>
			<tr>
				<td class="row1">Max. Avatar File Size<br /><span class="gensmall">For uploaded avatar files</span></td>
				<td class="row2"><input type="text" size="4" maxlength="10" name="avatar_filesize" value="{AVATAR_FILESIZE}"> Bytes</td>
			</tr>
			<tr>
				<td class="row1">Max. Avatar Size <br />
					<span class="gensmall">(height x width)</span>
				</td>
				<td class="row2"><input type="text" size="3" maxlength="4" name="avatar_max_height" value="{AVATAR_MAX_HEIGHT}"> x <input type="text" size="3" maxlength="4" name="avatar_max_width" value="{AVATAR_MAX_WIDTH}"></td>
			</tr>
			<tr>
				<td class="row1">Avatar Storage Path <br /><span class="gensmall">Path under your phpBB root dir, e.g. images/avatars</span></td>
				<td class="row2"><input type="text" size="20" maxlength="255" name="avatar_path" value="{AVATAR_PATH}"></td>
			</tr>
			<tr>
				<td class="cat" colspan="2"><span class="cattitle">Email Settings</span></td>
			</tr>
			<tr>
				<td class="row1">Admin Email Address</td>
				<td class="row2"><input type="text" size="25" maxlength="100" name="email_from" value="{EMAIL_FROM}"></td>
			</tr>
			<tr>
				<td class="row1">Email Signature<br /><span class="gensmall">This text will be attached to all emails the board sends</span></td>
				<td class="row2"><textarea name="email_sig" rows="5" cols="30">{EMAIL_SIG}</textarea></td>
			</tr>
			<tr>
				<td class="row1">Use SMTP for delivery<br /><span class="gensmall">Say yes if you want or have to send email via a server instead of the local mail function</span></td>
				<td class="row2"><input type="radio" name="smtp_delivery" value="1" {SMTP_YES}> Yes&nbsp;&nbsp;<input type="radio" name="smtp_delivery" value="0" {SMTP_NO}> No</td>
			</tr>
			<tr>
				<td class="row1">SMTP Server</td>
				<td class="row2"><input type="text" name="smtp_host" value="{SMTP_HOST}" size="25" maxlength="50"></td>
			</tr>
			<tr>
				<td class="cat" colspan="2" align="center">
					<input type="hidden" name="mode" value="config">
					<input type="submit" name="submit" value="Save Settings">
				</td>
			</tr>
		</table></td>
	</tr>
</table>

</form>

<br clear="all">
