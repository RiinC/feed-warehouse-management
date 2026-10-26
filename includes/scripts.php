<?php /** JavaScript dùng chung. $extra_scripts: HTML script bổ sung (tùy chọn) */ ?>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
<?= $extra_scripts ?? '' ?>
</body>
</html>
