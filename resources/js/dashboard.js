/* ------------------------------
   1. UTILITIES (Date, URL, Helpers)
   ------------------------------ */

// Show the current date and time (updates every second)
function updateDateTime() {
  const now = new Date();
  const options = {
    weekday: "long", year: "numeric", month: "long", day: "numeric",
    hour: "2-digit", minute: "2-digit", second: "2-digit",
  };
  // Put the formatted date & time inside the <div id="datetime">
  const datetimeEl = document.getElementById("datetime");
  if (datetimeEl) {
    datetimeEl.innerHTML = now.toLocaleDateString("en-US", options);
  }
}

// Get a value from the page URL (like ?project_id=123)
function getUrlParameter(name) {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get(name);
}

// Change the URL parameter without reloading the whole page
function updateUrlParameter(key, value) {
  const url = new URL(window.location);
  url.searchParams.set(key, value);
  window.history.replaceState({}, '', url);
}

// EMPTY STATES
function showEmptyStates() {
  // --- BUDGET ANALYSIS EMPTY STATE ---
  const budgetCtx = document.getElementById("budgetAnalysisChart");
  const budgetSummary = document.getElementById("budget-summary-display");
  const budgetEmptyId = "budgetEmptyState";

  if (budgetCtx) budgetCtx.style.display = "none";

  let budgetEmpty = document.getElementById(budgetEmptyId);
  if (!budgetEmpty) {
    budgetEmpty = document.createElement("div");
    budgetEmpty.id = budgetEmptyId;
    budgetEmpty.classList.add("empty-barChart");
    budgetCtx.parentNode.insertBefore(budgetEmpty, budgetCtx.nextSibling);
  }
  budgetEmpty.innerHTML = `
    <div>
      <img src="../resources/svg/empty-chart-budgetAnalysis.svg" alt="Empty Chart">
      <p>Select a project to view budget breakdown</p>
    </div>
  `;

  // --- EXPENSES BREAKDOWN EMPTY STATE ---
  const expensesCtx = document.getElementById("expensesBreakdownChart");
  const totalDisplay = document.getElementById("total-expenses-display");
  const expensesEmptyId = "expensesEmptyState";

  if (expensesCtx) expensesCtx.style.display = "none";
  if (totalDisplay) totalDisplay.textContent = "00.00";

  let expensesEmpty = document.getElementById(expensesEmptyId);
  if (!expensesEmpty) {
    expensesEmpty = document.createElement("div");
    expensesEmpty.id = expensesEmptyId;
    expensesEmpty.classList.add("empty-pieChart");
    expensesCtx.parentNode.insertBefore(expensesEmpty, expensesCtx.nextSibling);
  }
  expensesEmpty.innerHTML = `
    <div>
      <img src="../resources/svg/empty_pieChart.svg" alt="Empty Chart">
      <p>Select a project to view expenses</p>
    </div>
  `;

  // --- PROFIT/LOSS EMPTY STATE ---
  const profitLossCtx = document.getElementById("profitLossChart");
  const profitLossEmptyId = "profitLossEmptyState";

  if (profitLossCtx) profitLossCtx.style.display = "none";

  let profitLossEmpty = document.getElementById(profitLossEmptyId);
  if (!profitLossEmpty) {
    profitLossEmpty = document.createElement("div");
    profitLossEmpty.id = profitLossEmptyId;
    profitLossEmpty.classList.add("empty-chart");
    profitLossCtx.parentNode.insertBefore(profitLossEmpty, profitLossCtx.nextSibling);
  }
  profitLossEmpty.innerHTML = `
    <div>
      <img src="../resources/svg/empty-chart-PFP.svg" alt="Empty Chart">
      <p>Select a project to view Project Financial Performance</p>
    </div>
  `;
}

/* ------------------------------
   2. DATA FETCHING (AJAX Calls)
   ------------------------------ */

// Ask the backend for budget data (using AJAX)
function fetchBudgetData(projectId) {
  return $.ajax({
    url: "./fetch_budget_data.php",  // PHP file that provides data
    method: "GET",                   // send data using GET request
    data: { project_id: projectId }, // pass project id
    dataType: "json",               // expect JSON response
    timeout: 10000                  // 10 second timeout
  });
}

// Fetch expenses and financial data
function fetchFinancialData(projectId) {
  return $.ajax({
    url: "./fetch_data.php",
    method: "GET",
    data: { project_id: projectId },
    dataType: "json",
    timeout: 10000
  });
}

/* ------------------------------
   3. CHART FUNCTIONS
   ------------------------------ */

// Show Budget Analysis (bar chart comparing planned vs actual)
function createBudgetAnalysisChart(budgetData) {
  const canvas = document.getElementById("budgetAnalysisChart");
  if (!canvas) {
    console.error("Canvas element 'budgetAnalysisChart' not found");
    return;
  }

  // Destroy existing chart before creating new one
  if (window.budgetAnalysisChart instanceof Chart) {
    window.budgetAnalysisChart.destroy();
    window.budgetAnalysisChart = null;
  }

  const emptyStateContainerId = "budgetEmptyState"; // id for empty state
  let emptyStateContainer = document.getElementById(emptyStateContainerId);

  // If no data -> show empty state
  if (
    !budgetData ||
    (!budgetData.materials_budget &&
      !budgetData.labor_budget &&
      !budgetData.other_budget)
  ) {
    canvas.style.display = "none";

    // Create container if missing
    if (!emptyStateContainer) {
      emptyStateContainer = document.createElement("div");
      emptyStateContainer.id = emptyStateContainerId;
      emptyStateContainer.classList.add("empty-barChart"); // styled in CSS
      canvas.parentNode.insertBefore(emptyStateContainer, canvas.nextSibling);
    }

    // Empty State SVG & text
    emptyStateContainer.innerHTML = `
      <div>
        <img src="../resources/svg/empty-chart-budgetAnalysis.svg" alt="Empty Chart">
        <p>No budget data available for the selected project.</p>
      </div>
    `;

    return;
  }

  // If data exists -> show chart, hide empty state
  canvas.style.display = "block";
  if (emptyStateContainer) emptyStateContainer.innerHTML = "";

  // Update budget summary
  const budgetSummary = document.getElementById("budget-summary-display");
  if (budgetSummary) {
    const totalBudget =
      (budgetData.materials_budget || 0) +
      (budgetData.labor_budget || 0) +
      (budgetData.other_budget || 0);
    const totalSpent =
      (budgetData.materials_spent || 0) +
      (budgetData.labor_spent || 0) +
      (budgetData.other_spent || 0);
    const totalRemaining = totalBudget - totalSpent;
  }

  try {
    const ctx = canvas.getContext("2d");

    window.budgetAnalysisChart = new Chart(ctx, {
      type: "bar",
      data: {
        labels: ["Materials", "Labor", "Other Expenses"],
        datasets: [
          {
            label: "Allocated Budget",
            data: [
              budgetData.materials_budget || 0,
              budgetData.labor_budget || 0,
              budgetData.other_budget || 0,
            ],
            backgroundColor: "#3D679A",
            borderRadius: 5, // rounded bars
            barPercentage: 0.8, //  control thickness
            categoryPercentage: 0.7,

          },
          {
            label: "Actual Expenditures",
            data: [
              budgetData.materials_spent || 0,
              budgetData.labor_spent || 0,
              budgetData.other_spent || 0,
            ],
            backgroundColor: "#E53935",
            borderRadius: 5, //  rounded bars
            barPercentage: 0.8, //  control thickness
            categoryPercentage: 0.7,
      
          },
          {
            label: "Remaining Balance",
            data: [
              budgetData.materials_remaining || 0,
              budgetData.labor_remaining || 0,
              budgetData.other_remaining || 0,
            ],
            backgroundColor: "#E6B655",
            borderRadius: 5, //  rounded bars
            barPercentage: 0.8, //  control thickness
            categoryPercentage: 0.7,
        
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: true,
            position: "top",
            labels: {
              usePointStyle: true,
              pointStyle: "circle",
            },
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                return `${context.dataset.label}: ₱${context.raw.toLocaleString()}`;
              },
            },
          },
        },
        scales: {
          x: { stacked: false },
          y: {
            stacked: false,
            ticks: {
              callback: function (value) {
                if (value >= 1_000_000)
                  return "₱" + (value / 1_000_000).toFixed(1) + "M";
                if (value >= 1_000)
                  return "₱" + (value / 1_000).toFixed(0) + "K";
                return "₱" + value.toLocaleString();
              },
            },
          },
        },
      },
    });

    console.log("Budget chart created successfully");
  } catch (error) {
    console.error("Error creating budget chart:", error);
    canvas.style.display = "none";
  }
}

  // Show Expenses Breakdown (pie chart of categories)
function createExpensesBreakdownChart(expenseCategories, totalExpenses) {
  const canvas = document.getElementById("expensesBreakdownChart");
  if (!canvas) {
    console.error("Canvas element 'expensesBreakdownChart' not found");
    return;
  }

  // Destroy existing chart before creating new one
  if (window.expensesBreakdownChart instanceof Chart) {
    window.expensesBreakdownChart.destroy();
    window.expensesBreakdownChart = null;
  }

  const emptyStateContainerId = "expensesEmptyState"; // id of empty state
  let emptyStateContainer = document.getElementById(emptyStateContainerId);

  const categories = Object.keys(expenseCategories || {});
  const amounts = Object.values(expenseCategories || {});

  // If no categories or total = 0 -> show empty state
  if (!categories.length || totalExpenses === 0) {
    canvas.style.display = "none";

    // Create container if it doesn't exist
    if (!emptyStateContainer) {
      emptyStateContainer = document.createElement("div");
      emptyStateContainer.id = emptyStateContainerId;
      emptyStateContainer.classList.add("empty-pieChart"); // ✅ styled by CSS
      canvas.parentNode.insertBefore(emptyStateContainer, canvas.nextSibling);
    }

    // Empty State SVG
    emptyStateContainer.innerHTML = `
      <div>
        <img src="/Financial_Management/resources/svg/empty_pieChart.svg" alt="Empty Chart">
        <p>No expenses data available for the selected project.</p>
      </div>
    `;

    const totalDisplay = document.getElementById("total-expenses-display");
    if (totalDisplay) totalDisplay.textContent = "00.00";

    return;
  }

  // If data exists -> show chart, hide empty state
  canvas.style.display = "block";
  if (emptyStateContainer) emptyStateContainer.innerHTML = "";

  // Update total expenses display
  const totalDisplay = document.getElementById("total-expenses-display");
  if (totalDisplay) {
    totalDisplay.innerHTML = `<strong>₱${totalExpenses.toLocaleString("en-US", {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })}</strong>`;
  }

  try {
    const ctx = canvas.getContext("2d");

    // Create pie chart
    window.expensesBreakdownChart = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: categories.map(
          (cat) => cat.charAt(0).toUpperCase() + cat.slice(1)
        ),
        datasets: [
          {
            data: amounts,
            backgroundColor: [
  "#E74C3C", // Bright Red
  "#3498DB", // Bright Blue
  "#2ECC71", // Emerald Green
  "#F39C12", // Orange
  "#9B59B6", // Purple
  "#1ABC9C", // Turquoise
  "#E67E22", // Dark Orange
  "#34495E", // Dark Blue-Gray
  "#F1C40F", // Yellow
  "#E91E63", // Pink
  "#16A085", // Dark Turquoise
  "#8E44AD", // Dark Purple
  "#D35400", // Pumpkin
  "#27AE60", // Dark Green
  "#2980B9", // Strong Blue
  "#C0392B", // Dark Red
],


            borderColor: "rgba(255,255,255,0.8)",
            borderWidth: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: "right",
            display: true,
            labels: {
              usePointStyle: true,
              pointStyle: "circle",
              font: { size: 12 },
            },
          },
          tooltip: {
            callbacks: {
              label: function (context) {
                const value = context.raw;
                const percent = ((value / totalExpenses) * 100).toFixed(1);
                return `${
                  context.label
                }: ₱${value.toLocaleString()} (${percent}%)`;
              },
            },
          },
        },
      },
    });

    console.log(
      "Expenses chart created successfully with",
      categories.length,
      "categories"
    );
  } catch (error) {
    console.error("Error creating expenses chart:", error);
    canvas.style.display = "none";
  }
}

  // Show Project Financial Performance (line chart: income, expenses, net profit)
  function createProfitLossChart(labels, dailyServiceRevenue, dailyExpenses, dailyProfit) {
    const canvas = document.getElementById("profitLossChart");
    if (!canvas) {
      console.error("Canvas element 'profitLossChart' not found");
      return;
    }

    // Destroy existing chart
    if (window.myChartPL instanceof Chart) {
      window.myChartPL.destroy();
      window.myChartPL = null;
    }

    const emptyStateContainerId = "profitLossEmptyState"; // id of a div for empty state
    let emptyStateContainer = document.getElementById(emptyStateContainerId);

    // If labels are empty, hide canvas and show SVG empty state
    if (!labels || !labels.length) {
      canvas.style.display = "none";

      const emptyStateContainerId = "profitLossEmptyState";
      let emptyStateContainer = document.getElementById(emptyStateContainerId);

      // Create container if it doesn't exist
      if (!emptyStateContainer) {
        emptyStateContainer = document.createElement("div");
        emptyStateContainer.id = emptyStateContainerId;
        emptyStateContainer.classList.add("empty-chart"); // Add class for CSS
        canvas.parentNode.insertBefore(emptyStateContainer, canvas.nextSibling);
      }

      // Inject SVG image and message
      emptyStateContainer.innerHTML = `
        <div>
        <img src="/Financial_Management/resources/svg/empty-chart-PFP.svg" alt="Empty Chart">
        <p>No data available for the selected project.</p>
      </div>
      `;
      return;
    }

    // If data exists, show canvas and remove empty state
    canvas.style.display = "block";
    if (emptyStateContainer) emptyStateContainer.innerHTML = "";

    try {
      const ctx = canvas.getContext("2d");

      // Create line chart with 3 lines: Income (green), Expenses (orange), Profit (blue)
      window.myChartPL = new Chart(ctx, {
        type: "line",
        data: {
          labels: labels,
          datasets: [
            // {
            //   label: "Total Service Revenue",
            //   data: dailyServiceRevenue,
            //   borderColor: "#000000ff",
            //   backgroundColor: "rgba(74, 121, 56, 0.1)",
            //   fill: false,
            //   tension: 0.3,
            // },
            {
              label: "Total Expenses",
              data: dailyExpenses,
              borderColor: "#E6B655",
              backgroundColor: "rgba(229, 144, 76, 0.1)",
              fill: false,
              tension: 0.3,
            },
            {
              label: "Gross Profit",
              data: dailyProfit,
              borderColor: "#3D679A",
              backgroundColor: "rgba(111, 158, 178, 0.1)",
              fill: false,
              tension: 0.3,
            },
          ],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: true,
              position: "top",
              labels: {
                usePointStyle: true, // enables circle, triangle, star, etc.
                pointStyle: "circle", // makes legend markers circular
                padding: 20,
              },
            },
            tooltip: {
              callbacks: {
                label: function (context) {
                  return `${
                    context.dataset.label
                  }: ₱${context.raw.toLocaleString()}`;
                },
              },
            },
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function (value) {
                  if (value >= 1_000_000)
                    return "₱" + (value / 1_000_000).toFixed(1) + "M";
                  if (value >= 1_000)
                    return "₱" + (value / 1_000).toFixed(0) + "K";
                  return "₱" + value.toLocaleString();
                },
              },
            },
          },
        },
      });

      console.log("Profit/Loss chart created successfully");
    } catch (error) {
      console.error("Error creating profit/loss chart:", error);
      canvas.style.display = "none";
    }
  }

  /* ------------------------------
    4. DATA PROCESSORS/COMPUTATIONS
    ------------------------------ */

  // Prepare Expenses data (group by category, add totals)
  function processExpensesData(response) {
    const data = response.expenses || [];   // ✅ grab array properly
    console.log("Processing expenses data:", response);

    if (!Array.isArray(data)) {
      console.error("Expected array but got:", typeof data);
      return;
    }

    const expenseCategories = {};
    let totalExpenses = 0;

    data.forEach((item) => {   // ✅ loop on data, not response
      if (!item.category || !item.amount) return;

      const category = item.category.trim().toLowerCase();
      const amount = parseFloat(item.amount) || 0;

      if (category !== "income") {
        totalExpenses += amount;
        expenseCategories[category] = (expenseCategories[category] || 0) + amount;
      }
    });

    console.log("Processed expenses:", expenseCategories, "Total:", totalExpenses);
    createExpensesBreakdownChart(expenseCategories, totalExpenses);
  }

// Prepare Project Financial Performance data (daily totals for income vs expense)
function processProfitLossData(response) {
  const data = response.expenses || [];   
  const projectedBudget = parseFloat(response.projected_budget_cost) || 0; // ✅ total service revenue

  let totalExpenses = 0;
  const dailyData = {};

  data.forEach((item) => {
    if (!item.date || !item.amount || !item.category) return;

    const category = item.category.trim().toLowerCase();
    const amount = parseFloat(item.amount) || 0;
    const key = new Date(item.date).toISOString().split("T")[0]; // YYYY-MM-DD

    if (!dailyData[key]) dailyData[key] = { expense: 0 };

    // ✅ Only track expenses here
    if (category !== "income" && category !== "servicerevenue") {
      dailyData[key].expense += amount;
      totalExpenses += amount;
    }
  });

  // Sort by date
  const sortedKeys = Object.keys(dailyData).sort((a, b) => new Date(a) - new Date(b));
  const dailyLabels = sortedKeys.map(k => new Date(k).toLocaleDateString("en-US", {month:"short", day:"numeric"}));
  const dailyServiceRevenue = sortedKeys.map(() => projectedBudget); // ✅ same budget across days
  const dailyExpenses = sortedKeys.map(k => dailyData[k].expense);
  const dailyProfit = sortedKeys.map(k => projectedBudget - dailyData[k].expense);

  // Make chart
  createProfitLossChart(dailyLabels, dailyServiceRevenue, dailyExpenses, dailyProfit);

  // Update summary text
  const grossProfit = projectedBudget - totalExpenses; // ✅ guaranteed not negative unless expenses > budget

  const summaryEl = document.getElementById("profit-loss-summary");
  if (summaryEl) {
    summaryEl.innerHTML = `
      <strong>Total Expenses:</strong> ₱${totalExpenses.toLocaleString()}<br>
      <strong>Total Service Revenue:</strong> ₱${projectedBudget.toLocaleString()}<br>
      <strong>Gross Profit:</strong> ₱${grossProfit.toLocaleString()}
    `;
  }
}

/* ------------------------------
   5. MAIN DATA LOADER
   ------------------------------ */

// This is the main function that loads ALL charts for a project
function loadProjectData(projectId) {
  console.log("Loading data for project:", projectId);

  if (!projectId) {
    console.warn("No project ID provided");
    showEmptyStates();
    return;
  }

  // Show loading states
  const budgetSummary = document.getElementById('budget-summary-display');
  
  const totalDisplay = document.getElementById('total-expenses-display');
  if (totalDisplay) totalDisplay.textContent = 'Loading expenses data...';

  // 1. Load Budget Chart
  fetchBudgetData(projectId)
    .done(function(data) {
      console.log("Budget data received:", data);
      createBudgetAnalysisChart(data);
    })
    .fail(function(xhr, status, error) {
      console.error("Budget data fetch failed:", status, error);
      createBudgetAnalysisChart(null);
    });

  // 2. Load Expenses + Profit/Loss from backend
  fetchFinancialData(projectId)
    .done(function(response) {
      console.log("Financial data received:", response);
      processExpensesData(response);   
      processProfitLossData(response); 
    })
    .fail(function(xhr, status, error) {
      console.error("Financial data fetch failed:", status, error);
      console.error("Response text:", xhr.responseText);
      showEmptyStates();
    });
}

/* ------------------------------
   6. UI HANDLERS (Event Listeners)
   ------------------------------ */

// When user clicks a project in dropdown
$(document).on("click", ".project-select", function (e) {
  e.preventDefault();
  
  const projectId = $(this).data("id");
  const projectName = $(this).data("project");
  const clientName = $(this).data("client");
  const projectedBudget = $(this).data("budget");

  // Update popover content
  updatePopoverContent(projectName, projectedBudget);
  console.log("Project selected:", { projectId, projectName, clientName });

  // Update project name in UI
  const projectNameEl = document.getElementById("project-name");
  const clientNameEl = document.getElementById("client-name");
  
  if (projectNameEl) projectNameEl.textContent = projectName || 'Unknown Project';
  if (clientNameEl) clientNameEl.textContent = clientName ? `${clientName}` : '';

  // Update URL (so you can copy/share it)
  updateUrlParameter('project_id', projectId);

  // Load charts for that project
  updatePopoverContent(projectName, projectedBudget); 
  loadProjectData(projectId);
});

// Function to update the popover text
function updatePopoverContent(projectName, projectedBudget) {
  const popoverElement = document.querySelector('[data-bs-toggle="popover"]');
  
  if (popoverElement && projectedBudget) {
    const formattedBudget = parseFloat(projectedBudget).toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });

    const newContent = `This line graph presents the daily total expenses for project <em>${projectName}</em>. The gross profit is calculated by subtracting all expenses from the total project cost of <strong>₱${formattedBudget}</strong>.`;

    popoverElement.setAttribute('data-bs-content', newContent);
    
    // Refresh the popover
    const existingPopover = bootstrap.Popover.getInstance(popoverElement);
    if (existingPopover) {
      existingPopover.dispose();
    }
    new bootstrap.Popover(popoverElement);
  }
}

/* ------------------------------
   7. INIT (On Page Load)
   ------------------------------ */

$(document).ready(function () {
  console.log("Dashboard JavaScript initialized");

  // Start updating clock in top corner
  setInterval(updateDateTime, 1000);
  updateDateTime();

  // Check if canvas elements exist
  console.log("Canvas elements check:");
  console.log("- Budget canvas:", document.getElementById("budgetAnalysisChart"));
  console.log("- Expenses canvas:", document.getElementById("expensesBreakdownChart"));
  console.log("- Profit/Loss canvas:", document.getElementById("profitLossChart"));

  // If a project is already selected → load its data
  const projectId = window.phpData?.selectedProjectId || getUrlParameter('project_id');
  
  if (projectId) {
    console.log("Loading data for pre-selected project:", projectId);
    loadProjectData(projectId);
  } else {
    console.log("No project pre-selected, showing empty states");
    showEmptyStates(); // otherwise show "empty states"
  }

  // Global error handler for Chart.js
  Chart.defaults.plugins.legend.onClick = function(e, legendItem, legend) {
    const index = legendItem.datasetIndex;
    const chart = legend.chart;
    const meta = chart.getDatasetMeta(index);

    meta.hidden = meta.hidden === null ? !chart.data.datasets[index].hidden : null;
    chart.update();
  };
});

// Global error handling
window.addEventListener('error', function(e) {
  console.error('Global error:', e.error);
});

// Cleanup charts on page unload
window.addEventListener('beforeunload', function() {
  if (window.budgetAnalysisChart instanceof Chart) {
    window.budgetAnalysisChart.destroy();
  }
  if (window.expensesBreakdownChart instanceof Chart) {
    window.expensesBreakdownChart.destroy();
  }
  if (window.myChartPL instanceof Chart) {
    window.myChartPL.destroy();
  }
});

/* ------------------------------
   SIMPLE PROJECT PERSISTENCE - localStorage
   ------------------------------ */

// Save project to localStorage
function saveLastSelectedProject(projectId, projectName, clientName) {
  const projectData = {
    id: projectId,
    name: projectName,
    client: clientName,
    timestamp: Date.now()
  };
  
  try {
    localStorage.setItem('lastSelectedProject', JSON.stringify(projectData));
    console.log('Project saved to localStorage:', projectData);
  } catch (error) {
    console.warn('Failed to save project to localStorage:', error);
  }
}

// Get project from localStorage
function getLastSelectedProject() {
  try {
    const stored = localStorage.getItem('lastSelectedProject');
    if (stored) {
      const projectData = JSON.parse(stored);
      console.log('Project loaded from localStorage:', projectData);
      return projectData;
    }
  } catch (error) {
    console.warn('Failed to load project from localStorage:', error);
  }
  return null;
}

// Clear stored project (optional - for logout)
function clearLastSelectedProject() {
  try {
    localStorage.removeItem('lastSelectedProject');
    console.log('Project cleared from localStorage');
  } catch (error) {
    console.warn('Failed to clear project from localStorage:', error);
  }
}

// Modified project selection handler
$(document).on("click", ".project-select", function (e) {
  e.preventDefault();
  
  const projectId = $(this).data("id");
  const projectName = $(this).data("project");
  const clientName = $(this).data("client");

  console.log("Project selected:", { projectId, projectName, clientName });

  // Update project name in UI
  const projectNameEl = document.getElementById("project-name");
  const clientNameEl = document.getElementById("client-name");
  
  if (projectNameEl) projectNameEl.textContent = projectName || 'Unknown Project';
  if (clientNameEl) clientNameEl.textContent = clientName ? `${clientName}` : '';

  // Save to localStorage
  saveLastSelectedProject(projectId, projectName, clientName);

  // Update URL (for sharing/bookmarking)
  updateUrlParameter('project_id', projectId);

  // Highlight selected project
  highlightSelectedProject(projectId);

  // Load charts for that project
  loadProjectData(projectId);
});

// Helper function to highlight selected project
function highlightSelectedProject(projectId) {
  // Remove previous highlights
  $('.project-select').removeClass('active selected');
  
  // Add highlight to current project
  // $(`.project-select[data-id="${projectId}"]`).addClass('active selected');
}

// Load project and update UI
function loadStoredProject(projectData) {
  const { id: projectId, name: projectName, client: clientName } = projectData;
  
  // Update project name in UI
  const projectNameEl = document.getElementById("project-name");
  const clientNameEl = document.getElementById("client-name");
  
  if (projectNameEl) projectNameEl.textContent = projectName || 'Unknown Project';
  if (clientNameEl) clientNameEl.textContent = clientName ? ` ${clientName}` : '';

  // Update URL to reflect the loaded project
  updateUrlParameter('project_id', projectId);
  
  // Highlight the selected project
  highlightSelectedProject(projectId);
  
  // Load the project data
  loadProjectData(projectId);
}

// Enhanced initialization with localStorage check
$(document).ready(function () {
  console.log("Dashboard JavaScript initialized with localStorage persistence");

  // Start updating clock
  setInterval(updateDateTime, 1000);
  updateDateTime();

  // Check canvas elements
  console.log("Canvas elements check:");
  console.log("- Budget canvas:", document.getElementById("budgetAnalysisChart"));
  console.log("- Expenses canvas:", document.getElementById("expensesBreakdownChart"));
  console.log("- Profit/Loss canvas:", document.getElementById("profitLossChart"));

  // Check for project ID in order of priority:
  let projectId = null;
  let storedProject = null;

  // 1. First check URL parameter (highest priority - for direct links)
  projectId = window.phpData?.selectedProjectId || getUrlParameter('project_id');
  
  if (projectId) {
    console.log("Found project in URL:", projectId);
    // Still save to localStorage for future sessions
    const selectedElement = $(`.project-select[data-id="${projectId}"]`);
    if (selectedElement.length) {
      const projectName = selectedElement.data('project');
      const clientName = selectedElement.data('client');
      saveLastSelectedProject(projectId, projectName, clientName);
    }
    highlightSelectedProject(projectId);
    loadProjectData(projectId);
    return;
  }

  // 2. Check localStorage for last selected project
  storedProject = getLastSelectedProject();
  
  if (storedProject && storedProject.id) {
    console.log("Loading stored project:", storedProject);
    
    // Verify the project still exists in the DOM (user might not have access anymore)
    const projectElement = $(`.project-select[data-id="${storedProject.id}"]`);
    if (projectElement.length > 0) {
      loadStoredProject(storedProject);
      return;
    } else {
      console.log("Stored project no longer accessible, clearing localStorage");
      clearLastSelectedProject();
    }
  }

  // 3. No stored project or URL parameter
  console.log("No persistent project found, showing empty states");
  showEmptyStates();
});

// Optional: Add to your logout function to clear stored project
function handleLogout() {
  // Clear the stored project when user logs out
  clearLastSelectedProject();
}

// Optional: Clear old localStorage data (run once to clean up)
function clearOldStoredData() {
  try {
    // Remove any old storage keys you might have used
    localStorage.removeItem('selectedProjectId');
    localStorage.removeItem('projectData');
    // Add any other old keys here
  } catch (error) {
    console.warn('Failed to clear old localStorage data:', error);
  }
}

// Global error handling remains the same
window.addEventListener('error', function(e) {
  console.error('Global error:', e.error);
});

// Cleanup charts on page unload remains the same
window.addEventListener('beforeunload', function() {
  if (window.budgetAnalysisChart instanceof Chart) {
    window.budgetAnalysisChart.destroy();
  }
  if (window.expensesBreakdownChart instanceof Chart) {
    window.expensesBreakdownChart.destroy();
  }
  if (window.myChartPL instanceof Chart) {
    window.myChartPL.destroy();
  }
});