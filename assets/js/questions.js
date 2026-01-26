// Question Management JavaScript

let optionCount = 0;
let multiSelectCount = 0;

function showQuestionModal() {
  resetQuestionForm();
  $("#questionModal").modal("show");
}

function resetQuestionForm() {
  $("#modalAction").val("create");
  $("#questionId").val("");
  $("#modalTitle").html('<i class="fas fa-plus-circle"></i> Add New Question');
  $("#question_text").val("");
  $("#question_type").val("");
  $("#marks").val("1");
  $("#order_number").val("0");

  // Hide all question type options
  $(".question-type-options").hide();
  $("#optionsContainer").empty();
  $("#multiSelectContainer").empty();
  $("#correct_answer_text").val("");
  $('input[name="correct_answer"]').prop("checked", false);

  optionCount = 0;
  multiSelectCount = 0;
}

function changeQuestionType() {
  const type = $("#question_type").val();

  // Hide all options first
  $(".question-type-options").hide();

  // Show relevant options based on type
  if (type === "multiple_choice") {
    $("#multipleChoiceOptions").show();
    if (optionCount === 0) {
      for (let i = 0; i < 4; i++) {
        addOption();
      }
    }
  } else if (type === "multiple_select") {
    $("#multipleSelectOptions").show();
    if (multiSelectCount === 0) {
      for (let i = 0; i < 4; i++) {
        addMultiSelectOption();
      }
    }
  } else if (type === "true_false") {
    $("#trueFalseOptions").show();
  } else if (type === "short_answer" || type === "fill_blank") {
    $("#textAnswerOptions").show();
  }
}

function addOption() {
  const optionHtml = `
        <div class="input-group mb-2 option-row">
            <div class="input-group-prepend">
                <div class="input-group-text">
                    <input type="radio" name="correct_option" value="${optionCount}" required>
                </div>
            </div>
            <input type="text" class="form-control" name="options[]" placeholder="Option ${optionCount + 1}" required>
            <div class="input-group-append">
                <button class="btn btn-danger" type="button" onclick="removeOption(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
  $("#optionsContainer").append(optionHtml);
  optionCount++;
}

function addMultiSelectOption() {
  const optionHtml = `
        <div class="input-group mb-2 option-row">
            <div class="input-group-prepend">
                <div class="input-group-text">
                    <input type="checkbox" name="correct_options[]" value="${multiSelectCount}">
                </div>
            </div>
            <input type="text" class="form-control" name="options[]" placeholder="Option ${multiSelectCount + 1}" required>
            <div class="input-group-append">
                <button class="btn btn-danger" type="button" onclick="removeMultiSelectOption(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
  $("#multiSelectContainer").append(optionHtml);
  multiSelectCount++;
}

function removeOption(button) {
  $(button).closest(".option-row").remove();
}

function removeMultiSelectOption(button) {
  $(button).closest(".option-row").remove();
}

function editQuestion(question, questionId) {
  resetQuestionForm();

  $("#modalAction").val("edit");
  $("#questionId").val(questionId);
  $("#modalTitle").html('<i class="fas fa-edit"></i> Edit Question');
  $("#question_text").val(question.question_text);
  $("#question_type").val(question.question_type);
  $("#marks").val(question.marks);
  $("#order_number").val(question.order_number);

  changeQuestionType();

  // Load existing data based on question type
  if (
    question.question_type === "multiple_choice" ||
    question.question_type === "multiple_select"
  ) {
    // Fetch options via AJAX
    $.ajax({
      url: "../api/get-question-options.php",
      method: "GET",
      data: { question_id: questionId },
      dataType: "json",
      success: function (options) {
        if (question.question_type === "multiple_choice") {
          $("#optionsContainer").empty();
          optionCount = 0;
          options.forEach(function (option) {
            const optionHtml = `
                            <div class="input-group mb-2 option-row">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <input type="radio" name="correct_option" value="${optionCount}" ${option.is_correct == 1 ? "checked" : ""} required>
                                    </div>
                                </div>
                                <input type="text" class="form-control" name="options[]" value="${escapeHtml(option.option_text)}" required>
                                <div class="input-group-append">
                                    <button class="btn btn-danger" type="button" onclick="removeOption(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        `;
            $("#optionsContainer").append(optionHtml);
            optionCount++;
          });
        } else {
          $("#multiSelectContainer").empty();
          multiSelectCount = 0;
          options.forEach(function (option) {
            const optionHtml = `
                            <div class="input-group mb-2 option-row">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <input type="checkbox" name="correct_options[]" value="${multiSelectCount}" ${option.is_correct == 1 ? "checked" : ""}>
                                    </div>
                                </div>
                                <input type="text" class="form-control" name="options[]" value="${escapeHtml(option.option_text)}" required>
                                <div class="input-group-append">
                                    <button class="btn btn-danger" type="button" onclick="removeMultiSelectOption(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        `;
            $("#multiSelectContainer").append(optionHtml);
            multiSelectCount++;
          });
        }
      },
    });
  } else if (question.question_type === "true_false") {
    $(
      'input[name="correct_answer"][value="' + question.correct_answer + '"]',
    ).prop("checked", true);
  } else {
    $("#correct_answer_text").val(question.correct_answer);
  }

  $("#questionModal").modal("show");
}

function deleteQuestion(questionId) {
  $("#deleteQuestionId").val(questionId);
  $("#deleteModal").modal("show");
}

function escapeHtml(text) {
  const map = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  };
  return text.replace(/[&<>"']/g, function (m) {
    return map[m];
  });
}

// Form validation
$("#questionForm").on("submit", function (e) {
  const questionType = $("#question_type").val();

  if (questionType === "multiple_choice") {
    if ($('input[name="correct_option"]:checked').length === 0) {
      e.preventDefault();
      alert("Please select the correct answer option.");
      return false;
    }
  } else if (questionType === "multiple_select") {
    if ($('input[name="correct_options[]"]:checked').length === 0) {
      e.preventDefault();
      alert("Please select at least one correct answer.");
      return false;
    }
  } else if (questionType === "true_false") {
    if ($('input[name="correct_answer"]:checked').length === 0) {
      e.preventDefault();
      alert("Please select True or False as the correct answer.");
      return false;
    }
  }
});
