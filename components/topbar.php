<div class="top-bar">
    <div class="top-bar-title">
        <p class="welcome-message"><span>Hello</span>, welcome</p>
    </div>
    <div class="top-bar-page">
        <p class="page-name"><a href="dashboard.php">Home</a> | <span id="page-title"></span></p>
    </div>
</div>
<script>
    const page_url = window.location.href
    const page_name = new URL(page_url).pathname.split("/").pop(); 
    page_title = document.getElementById("page-title");
    if(page_name === "dashboard.php"){
        page_title.innerHTML = "Dashboard";
    }else if(page_name == "contributions.php"){
        page_title.innerHTML = "Add Contribution"
    }else if(page_name == "loans.php"){
        page_title.innerHTML = "Loans"
    }else if(page_name == "loan_repayments.php"){
        page_title.innerHTML = "Reports | Repayment"
    }else if(page_name == "loan_history.php"){
        page_title.innerHTML = "Reports | History"
    }else if(page_name == "loan_report.php"){
        page_title.innerHTML = "Reports | Repayment"
    }else if(page_name == "contributions_history.php"){
        page_title.innerHTML = "Reports | Contributions"
    }else if(page_name == "loan_summaries.php"){
        page_title.innerHTML = "Reports | Summary"
    }
</script>