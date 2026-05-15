import React, {useState} from "react";
import {TemporaryEmailBox} from "../../types/types";
import copy from "copy-to-clipboard";

interface Props {
  temporaryEmailBox: TemporaryEmailBox|null;
  handleRegenerateEmail: () => void;
  handleUseCustomName: (name: string) => Promise<void>;
}

const NAME_PATTERN = /^[A-Za-z0-9](?:[A-Za-z0-9._-]*[A-Za-z0-9])?$/;

const Generator = ({temporaryEmailBox, handleRegenerateEmail, handleUseCustomName}: Props) => {
  const [copied, setCopied] = useState(false);
  const [customName, setCustomName] = useState("");
  const [customError, setCustomError] = useState<string | null>(null);
  const [customBusy, setCustomBusy] = useState(false);

  const handleCopy = () => {
    if (temporaryEmailBox === null) {
      return;
    }

    copy(temporaryEmailBox.email);
    setCopied(true);
  }

  const handleRegenerateButtonPress = () => {
    handleRegenerateEmail();
    setCopied(false);
  }

  const handleCustomSubmit = () => {
    const trimmed = customName.trim();
    if (trimmed === "") {
      setCustomError("Please enter a name.");
      return;
    }
    if (!NAME_PATTERN.test(trimmed) || trimmed.length > 64) {
      setCustomError("Letters, digits, dot, underscore and hyphen only (max 64).");
      return;
    }

    setCustomError(null);
    setCustomBusy(true);
    handleUseCustomName(trimmed)
      .then(() => {
        setCopied(false);
        setCustomName("");
      })
      .catch(e => {
        setCustomError(e?.response?.data?.error ?? "Could not use this name.");
      })
      .finally(() => setCustomBusy(false));
  }

  return (
    <section className="hero is-dark">
      <div className="hero-body">
        <div className="container">
          <div className="columns is-centered">
            <div className="column is-8">
              <h1 className="title is-5 has-text-centered has-text-white mb-4">
                Your Temporary Email Address
              </h1>

              <div className="columns is-mobile is-multiline">
                <div className="column is-12-mobile is-9-tablet">
                  <label>
                    <input
                      className="input is-medium has-text-weight-semibold is-size-6-mobile"
                      type="text"
                      value={temporaryEmailBox === null ? 'Loading ...' : temporaryEmailBox.email}
                      readOnly
                    />
                  </label>
                </div>

                <div className="column is-12-mobile is-3-tablet">
                  <button className="button is-primary is-medium is-fullwidth" onClick={() => handleCopy()}>
                    <span className="icon">
                      <i className={copied ? "fas fa-check" : "fas fa-copy"}></i>
                    </span>
                    <span>{copied ? 'Copied!' : 'Copy'}</span>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div className="buttons is-centered">
            <div className="is-inline">
              <button className="button is-light" onClick={() => handleRegenerateButtonPress()}>
                <span className="icon"><i className="fas fa-sync-alt"></i></span>
                <span>Regenerate Email</span>
              </button>
            </div>
          </div>

          <div className="columns is-centered">
            <div className="column is-8">
              <p className="has-text-centered has-text-white is-size-6 mb-2">
                Or pick a custom name (internal use):
              </p>
              <div className="field has-addons">
                <div className="control is-expanded">
                  <input
                    className="input is-medium"
                    type="text"
                    placeholder="e.g. team-alpha"
                    value={customName}
                    disabled={customBusy}
                    onChange={(e) => setCustomName(e.target.value)}
                    onKeyDown={(e) => {
                      if (e.key === "Enter") {
                        handleCustomSubmit();
                      }
                    }}
                  />
                </div>
                <div className="control">
                  <button
                    className={"button is-primary is-medium" + (customBusy ? " is-loading" : "")}
                    disabled={customBusy}
                    onClick={() => handleCustomSubmit()}
                  >
                    <span className="icon"><i className="fas fa-check"></i></span>
                    <span>Use this name</span>
                  </button>
                </div>
              </div>
              {customError && (
                <p className="help is-danger has-text-white">{customError}</p>
              )}
            </div>
          </div>

          <div className="columns is-centered">
            <div className="column is-8">
              <p className="has-text-centered has-text-white is-size-6">
                No more spam, marketing emails, or hacker attacks. Keep your real mailbox safe and
                tidy with TramSangTao Mail, a free, temporary, anonymous, and secure email address.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

export default Generator;
