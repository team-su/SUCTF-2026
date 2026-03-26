//成功提示
function SucceedBox(title, time){
	var title = arguments[0] ? arguments[0] : '成功';
	var time = arguments[1] ? arguments[1] : 3000;
	$.toast(title, time);
}

//错误提示对话框
function ErrorBox(title){
	$.alert(title);
}

//关闭加载中
function CloseLoadBox(){
	$.hideLoading();
}

//显示正在加载中
function LoadBox(){
	var title = arguments[0] ? arguments[0] : '处理中';
	$.showLoading(title);
}