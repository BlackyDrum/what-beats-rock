<script setup>
import { nextTick, onBeforeMount, onMounted, ref } from "vue";
import { useToast } from "primevue/usetoast";
import { Head, Link, router, usePage } from "@inertiajs/vue3";

import Toast from "primevue/toast";
import { Button } from "primevue";

import DataTable from "primevue/datatable";
import Column from "primevue/column";

const toast = useToast();
const page = usePage();

const prevGuess = ref("rock");
const currentGuess = ref("");
const currentEmoji = ref("🪨");
const currentExplanation = ref("");
const currentGid = ref("");

const prevGuessesList = ref(["rock"]);

const isSendingRequest = ref(false);

const gameLost = ref(false);
const score = ref(0);

const input = ref();

onMounted(() => {
    input.value.focus();
});

function guess() {
    if (currentGuess.value.length === 0 || isSendingRequest.value) return;

    if (prevGuessesList.value.includes(currentGuess.value.toLowerCase())) {
        toast.add({
            severity: "error",
            summary: "Error",
            detail: "No repeated guesses! Try something else.",
            life: 5000,
        });

        return;
    }

    if (currentGid.value.length === 0) {
        currentGid.value = uuidv4();
    }

    isSendingRequest.value = true;

    window.axios
        .post("/guess", {
            gid: currentGid.value,
            guess: currentGuess.value.toLowerCase(),
        })
        .then((response) => {
            if (response.data.won) {
                prevGuess.value = currentGuess.value.toLowerCase();
                prevGuessesList.value.push(prevGuess.value.toLowerCase());

                currentEmoji.value = response.data.emoji;

                score.value++;
            } else {
                gameLost.value = true;

                if (score.value > page.props.highscore) {
                    page.props.highscore = score.value;
                }

                router.reload({ only: ["leaderboard"] });
            }

            currentExplanation.value = response.data.explanation;
        })
        .catch((error) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });
        })
        .finally(() => {
            currentGuess.value = "";

            isSendingRequest.value = false;

            nextTick(() => {
                if (!gameLost.value) {
                    input.value.focus();
                }
            });
        });
}

function handleInput(event) {
    currentGuess.value = currentGuess.value.toLowerCase();
}

function restart() {
    gameLost.value = false;
    score.value = 0;
    prevGuess.value = "rock";
    currentGuess.value = "";
    currentEmoji.value = "🪨";
    currentExplanation.value = "";
    currentGid.value = uuidv4();
    prevGuessesList.value = ["rock"];

    nextTick(() => {
        input.value.focus();
    });
}

function uuidv4() {
    return "10000000-1000-4000-8000-100000000000".replace(/[018]/g, (c) =>
        (
            +c ^
            (crypto.getRandomValues(new Uint8Array(1))[0] & (15 >> (+c / 4)))
        ).toString(16),
    );
}
</script>

<template>
    <Toast />

    <Head title="What beats rock" />

    <div class="bg-gray-100">
        <div class="p-4">
            <Link
                v-if="!page.props.auth.user"
                href="/login"
                class="font-bold underline"
                >Sign In</Link
            >
            <Link
                v-else
                href="/logout"
                method="post"
                class="font-bold underline"
                >Logout</Link
            >
        </div>

        <div class="mx-4 h-dvh sm:mx-32 md:mx-48 lg:mx-72 xl:mx-96">
            <div
                class="relative top-[20%] flex w-full flex-col items-center justify-start pb-48"
            >
                <p class="text-3xl font-bold">what beats</p>
                <p class="text-center text-2xl">{{ prevGuess }}?</p>

                <p class="mt-2 w-1/2 text-center">{{ currentExplanation }}</p>

                <p class="my-5 text-5xl">
                    {{ gameLost ? "❌" : currentEmoji }}
                </p>

                <form @submit.prevent="guess" v-if="!gameLost">
                    <input
                        v-model="currentGuess"
                        @input="handleInput"
                        :disabled="isSendingRequest"
                        ref="input"
                        class="border-1-black border py-4 pl-4 text-lg"
                    /><button
                        :disabled="isSendingRequest"
                        type="submit"
                        class="border-1-black border bg-green-200 p-4 text-lg"
                    >
                        <div v-if="isSendingRequest">
                            <i class="pi pi-spin pi-spinner"></i>
                        </div>
                        <div v-else>GO</div>
                    </button>
                </form>

                <div class="mt-2 text-sm opacity-70">
                    Score: {{ score }}
                    <span
                        v-if="!page.props.auth.user"
                        class="text-xs font-semibold"
                    >
                        (<Link class="underline" href="/login">Sign In</Link> to
                        view highscore)
                    </span>
                    <span v-else class="text-xs font-semibold">
                        (Highscore: {{ page.props.highscore }})</span
                    >
                </div>

                <div
                    class="mt-3 flex w-1/2 flex-wrap justify-center"
                    v-if="prevGuessesList.length > 1"
                >
                    <div
                        v-for="(prevGuess, index) in prevGuessesList
                            .slice()
                            .reverse()"
                    >
                        <span class="mx-1 font-semibold">{{ prevGuess }}</span>
                        <template v-if="index !== prevGuessesList.length - 1"
                            >🤜</template
                        >
                    </div>
                </div>

                <div v-if="gameLost" class="w-full text-center">
                    <Button
                        class="mt-10 w-1/2"
                        severity="info"
                        @click="restart"
                        label="Restart"
                    />
                </div>
            </div>
            <div class="mx-auto mt-20 text-center xl:w-3/4">
                <DataTable :value="page.props.leaderboard">
                    <Column field="rank" header="Rank"></Column>
                    <Column field="name" header="Player"></Column>
                    <Column field="highscore" header="Score"></Column>
                    <Column field="lastGuess" header="Last Guess"></Column>
                    <Column field="lostTo" header="Lost To"></Column>
                </DataTable>
            </div>
        </div>
    </div>
</template>

<style>
.p-message-error {
    word-break: break-word;
}
.p-toast {
    max-width: calc(100vw - 40px);
    word-break: break-word;
}
</style>
