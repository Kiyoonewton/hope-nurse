// Global variables
let optionCount = 0;
let multiSelectCount = 0;

// Show question modal
function showQuestionModal() {
  resetQuestionForm();
  $("#questionModal").modal("show");
}

// Reset question form
function resetQuestionForm() {
  $("#modalAction").val("create");
  $("#questionId").val("");
  $("#modalTitle").html('<i class="fas fa-plus-circle"></i> Add New Question');
  $("#question_text").val("");
  $("#question_type").val("");
  $("#marks").val("1");
  $("#order_number").val("0");
  $("#correct_answer_text").val("");

  // Hide all question type options
  $(".question-type-options").hide();

  // Reset radio buttons
  $('input[name="correct_answer"]').prop("checked", false);

  // Clear option containers
  $("#optionsContainer").empty();
  $("#multiSelectContainer").empty();
  optionCount = 0;
  multiSelectCount = 0;
}

// Change question type
function changeQuestionType() {
  const questionType = $("#question_type").val();

  // Hide all options first
  $(".question-type-options").hide();

  // Show relevant option based on type
  switch (questionType) {
    case "multiple_choice":
      $("#multipleChoiceOptions").show();
      // Add default options if empty
      if ($("#optionsContainer").children().length === 0) {
        addOption();
        addOption();
        addOption();
        addOption();
      }
      break;

    case "multiple_select":
      $("#multipleSelectOptions").show();
      // Add default options if empty
      if ($("#multiSelectContainer").children().length === 0) {
        addMultiSelectOption();
        addMultiSelectOption();
        addMultiSelectOption();
        addMultiSelectOption();
      }
      break;

    case "true_false":
      $("#trueFalseOptions").show();
      break;

    case "short_answer":
    case "fill_blank":
      $("#textAnswerOptions").show();
      break;
  }
}

// Add multiple choice option
function addOption() {
  const index = optionCount++;
  const optionHtml = `
        <div class="form-group option-item" id="option_${index}">
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <div class="input-group-text">
                        <input type="radio" name="correct_option" value="${index}" required>
                    </div>
                </div>
                <input type="text" class="form-control" name="options[]" placeholder="Enter option text" required>
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger" onclick="removeOption(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
  $("#optionsContainer").append(optionHtml);
}

// Remove multiple choice option
function removeOption(index) {
  $("#option_" + index).remove();
}

// Add multiple select option
function addMultiSelectOption() {
  const index = multiSelectCount++;
  const optionHtml = `
        <div class="form-group option-item" id="multi_option_${index}">
            <div class="input-group mb-2">
                <div class="input-group-prepend">
                    <div class="input-group-text">
                        <input type="checkbox" name="correct_options[]" value="${index}">
                    </div>
                </div>
                <input type="text" class="form-control" name="options[]" placeholder="Enter option text" required>
                <div class="input-group-append">
                    <button type="button" class="btn btn-danger" onclick="removeMultiSelectOption(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
  $("#multiSelectContainer").append(optionHtml);
}

// Remove multiple select option
function removeMultiSelectOption(index) {
  $("#multi_option_" + index).remove();
}

// Edit question
function editQuestion(question, questionId) {
  resetQuestionForm();

  $("#modalAction").val("edit");
  $("#questionId").val(question.id);
  $("#modalTitle").html('<i class="fas fa-edit"></i> Edit Question');
  $("#question_text").val(question.question_text);
  $("#question_type").val(question.question_type);
  $("#marks").val(question.marks);
  $("#order_number").val(question.order_number);

  // Load options based on question type
  if (
    question.question_type === "multiple_choice" ||
    question.question_type === "multiple_select"
  ) {
    // Fetch options via AJAX
    $.get(
      "../api/get-question-options.php",
      { question_id: questionId },
      function (options) {
        if (question.question_type === "multiple_choice") {
          $("#multipleChoiceOptions").show();
          options.forEach(function (option, index) {
            const optionHtml = `
                        <div class="form-group option-item" id="option_${index}">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <input type="radio" name="correct_option" value="${index}" ${option.is_correct ? "checked" : ""} required>
                                    </div>
                                </div>
                                <input type="text" class="form-control" name="options[]" value="${escapeHtml(option.option_text)}" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-danger" onclick="removeOption(${index})">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
            $("#optionsContainer").append(optionHtml);
            optionCount = index + 1;
          });
        } else {
          $("#multipleSelectOptions").show();
          options.forEach(function (option, index) {
            const optionHtml = `
                        <div class="form-group option-item" id="multi_option_${index}">
                            <div class="input-group mb-2">
                                <div class="input-group-prepend">
                                    <div class="input-group-text">
                                        <input type="checkbox" name="correct_options[]" value="${index}" ${option.is_correct ? "checked" : ""}>
                                    </div>
                                </div>
                                <input type="text" class="form-control" name="options[]" value="${escapeHtml(option.option_text)}" required>
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-danger" onclick="removeMultiSelectOption(${index})">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
            $("#multiSelectContainer").append(optionHtml);
            multiSelectCount = index + 1;
          });
        }
      },
      "json",
    );
  } else if (question.question_type === "true_false") {
    $("#trueFalseOptions").show();
    if (question.correct_answer === "True") {
      $("#true_option").prop("checked", true);
    } else {
      $("#false_option").prop("checked", true);
    }
  } else if (
    question.question_type === "short_answer" ||
    question.question_type === "fill_blank"
  ) {
    $("#textAnswerOptions").show();
    $("#correct_answer_text").val(question.correct_answer);
  }

  $("#questionModal").modal("show");
}

// Delete question
function deleteQuestion(questionId) {
  $("#deleteQuestionId").val(questionId);
  $("#deleteModal").modal("show");
}

// Escape HTML to prevent XSS
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
$(document).ready(function () {
  $("#questionForm").on("submit", function (e) {
    const questionType = $("#question_type").val();

    if (!questionType) {
      e.preventDefault();
      alert("Please select a question type");
      return false;
    }

    // Validate based on question type
    if (questionType === "multiple_choice") {
      if ($('input[name="correct_option"]:checked').length === 0) {
        e.preventDefault();
        alert("Please select the correct answer");
        return false;
      }

      if ($('input[name="options[]"]').length < 2) {
        e.preventDefault();
        alert("Please add at least 2 options");
        return false;
      }
    } else if (questionType === "multiple_select") {
      if ($('input[name="correct_options[]"]:checked').length === 0) {
        e.preventDefault();
        alert("Please select at least one correct answer");
        return false;
      }

      if ($('input[name="options[]"]').length < 2) {
        e.preventDefault();
        alert("Please add at least 2 options");
        return false;
      }
    } else if (questionType === "true_false") {
      if ($('input[name="correct_answer"]:checked').length === 0) {
        e.preventDefault();
        alert("Please select the correct answer (True or False)");
        return false;
      }
    } else if (
      questionType === "short_answer" ||
      questionType === "fill_blank"
    ) {
      if ($("#correct_answer_text").val().trim() === "") {
        e.preventDefault();
        alert("Please enter the expected answer");
        return false;
      }
    }

    return true;
  });
});
