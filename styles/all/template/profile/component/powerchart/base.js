var profile_id = $('input[name="snp_profile_id"]').val();
$.get("/app.php/snahp/achievements/powerchart/?u=" + profile_id, (resp) => {
  var angleLines = {};
  var pointLabels = {};
  var chartOptions = {};
  chartOptions.fontFamily = "'Ubuntu', 'Arial'";
  Chart.defaults.global.tooltips.enabled = false;
  chartOptions.color_a0 = "rgba(255, 45, 30, 0.3)";
  chartOptions.color_a1 = "rgba(255, 45, 30, 1)";
  chartOptions.color_b0 = "rgba(250, 160, 80, 0.4)";
  chartOptions.color_b1 = "rgba(250, 160, 80, 1)";
  switch (resp.stylename) {
    case "Acieeed!":
    case "Hexagon":
      angleLines.color = "#ddd";
      pointLabels.fontColor = "#bbb";
      chartOptions.pointBorderColor = "#262626";
      break;
    case "Basic":
      angleLines.color = "#222";
      pointLabels.fontColor = "#444";
      chartOptions.pointBorderColor = "#262626";
      break;
    case "prosilver":
      chartOptions.color_a0 = "rgba(255, 35, 0, 0.3)";
      chartOptions.color_a1 = "rgba(255, 35, 0, 1)";
      angleLines.color = "#222";
      pointLabels.fontColor = "#444";
      chartOptions.pointBorderColor = "#262626";
      break;
    default:
      break;
  }
  new Chart(document.getElementById("radar-chart"), {
    type: "radar",
    data: {
      labels: resp.labels,
      datasets: [
        {
          label: resp.user.name,
          fill: true,
          backgroundColor: chartOptions.color_a0,
          borderColor: chartOptions.color_a1,
          pointBackgroundColor: chartOptions.color_a1,
          pointBorderColor: chartOptions.pointBorderColor,
          pointBorderWidth: 2,
          pointRadius: 4,
          data: resp.user.data,
        },
        {
          label: resp.average.name,
          fill: true,
          backgroundColor: chartOptions.color_b0,
          borderColor: chartOptions.color_b1,
          pointBackgroundColor: chartOptions.color_b1,
          pointBorderColor: chartOptions.pointBorderColor,
          pointBorderWidth: 2,
          pointRadius: 4,
          data: resp.average.data,
        },
      ],
    },
    options: {
      title: {
        display: false,
        text: "Distribution in % of world population",
      },
      legend: {
        display: true,
        position: "top",
        labels: {
          boxWidth: 10,
          fontSize: 14,
          fontFamily: chartOptions.fontFamily,
          fontStyle: "bold",
          fontColor: pointLabels.fontColor,
        },
      },
      scale: {
        ticks: {
          display: false,
          suggestedMin: 0,
          max: resp.maximum,
          stepSize: 1000,
        },
        pointLabels: {
          display: true,
          fontSize: 15,
          fontFamily: chartOptions.fontFamily,
          fontStyle: "bold",
          fontColor: pointLabels.fontColor,
        },
        gridLines: {
          display: false,
          lineWidth: 2,
          color: angleLines.color,
        },
        angleLines: {
          display: true,
          lineWidth: 2,
          color: angleLines.color,
        },
      },
    },
  });
});
